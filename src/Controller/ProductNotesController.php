<?php

namespace App\Controller;

use App\Entity\ClinicUsers;
use App\Entity\ProductNotes;
use App\Entity\Products;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductNotesController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    private function noteCreateNew($product_id)
    {
        return '
            <div class="row mt-4">
                <div class="col-12">
                    <form name="form_note_'. $product_id .'" class="form_note" id="form_note_'. $product_id .'" method="post"  data-product-id="'. $product_id .'">
                        <input type="hidden" name="product_id" value="'. $product_id .'">
                        <input type="hidden" name="note_id" id="note_id_'. $product_id .'" value="0">
                        <div class="row">
                            <div class="col-12 col-sm-10 mb-3 mb-sm-0">
                                <input type="text" name="note" id="note_'. $product_id .'" class="form-control">
                                <div class="hidden_msg" id="error_note_'. $product_id .'">
                                    Required Field
                                </div>
                            </div>
                            <div class="col-12 col-sm-2">
                                <button type="submit" class="btn btn-primary float-end w-100">
                                    <i class="fa-solid fa-circle-plus"></i>
                                    &nbsp;ADD NEW NOTE
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>';
    }

    #[Route('/clinics/inventory/get-notes', name: 'inventory_get_notes')]
    public function clinicsGetNotesAction(Request $request): Response
    {
        $product_id = (int) $request->request->get('id');
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $product = $this->em->getRepository(Products::class)->find($product_id);
        $product_notes = $this->em->getRepository(ProductNotes::class)->findBy([
            'clinic' => $clinic,
            'product' => $product,
        ]);

        $response = '<h3 class="pb-3 pt-3">Item Notes</h3>';

        foreach($product_notes as $note){

            $response .= '<div class="row">
                            <div class="col-9">
                                <h6>'. $note->getNote() .'</h6>
                            </div>
                            <div class="col-3">
                                <a href="" class="float-end note_update" data-id="'. $note->getId() .'">
                                    <i class="fa-solid fa-pencil"></i>
                                </a>
                                <a href="" class="delete-icon float-end" data-bs-toggle="modal" data-note-id="'. $note->getId() .'" data-product-id="'. $product->getId() .'" data-bs-target="#modal_note_delete" id="note_delete_'. $note->getId() .'">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-12 info-sm">
                                '. $note->getClinicUser()->getFirstName() .' '. $note->getClinicUser()->getLastName() .' . '. $note->getCreated()->format('M d Y H:i') .'
                            </div>
                        </div>';
        }

        $response .= $this->noteCreateNew($product_id);

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/manage-note', name: 'inventory_manage_note')]
    public function clinicsManageNoteAction(Request $request): Response
    {
        $data = $request->request;
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $product = $this->em->getRepository(Products::class)->find($data->get('product_id'));
        $delete = $data->get('delete_id');
        $note_id = (int)$data->get('note_id');

        if($delete){

            $note = $this->em->getRepository(ProductNotes::class)->find($note_id);

            $this->em->remove($note);
            $this->em->flush();

        } else {

            $user_name = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
            $clinic_user = $this->em->getRepository(ClinicUsers::class)->findBy(['email' => $user_name]);

            $note_string = $data->get('note');

            // List
            if ($note_id == 0) {

                $note = new ProductNotes();

            } else {

                $note = $this->em->getRepository(ProductNotes::class)->find($note_id);
            }

            $note->setProduct($product);
            $note->setClinic($clinic);
            $note->setClinicUser($clinic_user[0]);
            $note->setNote($note_string);

            $this->em->persist($note);
            $this->em->flush();
        }

        // Get the updated list
        $product_notes = $this->em->getRepository(ProductNotes::class)->findBy([
            'clinic' => $clinic,
            'product' => $product,
        ]);
        $response = '';

        foreach($product_notes as $note){

            $response .= '<div class="row">
                            <div class="col-10">
                                <h6>'. $note->getNote() .'</h6>
                            </div>
                            <div class="col-2">
                                <a href="" class="float-end note_update" data-id="'. $note->getId() .'">
                                    <i class="fa-solid fa-pencil"></i>
                                </a>
                                <a href="" class="delete-icon float-end" data-bs-toggle="modal" data-note-id="'. $note->getId() .'" data-product-id="'. $product->getId() .'" data-bs-target="#modal_note_delete" id="note_delete_'. $note->getId() .'">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-12 info-sm">
                                '. $note->getClinicUser()->getFirstName() .' '. $note->getClinicUser()->getLastName() .' . '. $note->getCreated()->format('M d Y H:i') .'
                            </div>
                        </div>';
        }

        $response .= $this->noteCreateNew($product->getId());

        return new JsonResponse($response);
    }

    #[Route('/clinics/inventory/get-note', name: 'inventory_get_note')]
    public function clinicsGetNoteAction(Request $request): Response
    {
        $note = $this->em->getRepository(ProductNotes::class)->find($request->request->get('id'));

        $response = [
            'note' => $note->getNote(),
            'product_id' => $note->getProduct()->getId(),
        ];

        return new JsonResponse($response);
    }
}
