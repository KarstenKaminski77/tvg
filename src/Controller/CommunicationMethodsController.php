<?php

namespace App\Controller;

use App\Entity\ClinicCommunicationMethods;
use App\Entity\Clinics;
use App\Entity\CommunicationMethods;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommunicationMethodsController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    private function getCommunicationMethods()
    {
        $clinic = $this->getUser()->getClinic();
        $clinic_communication_methods = $this->em->getRepository(ClinicCommunicationMethods::class)->findBy([
            'clinic' => $clinic->getId(),
            'isActive' => 1,
        ]);
        $communication_methods = $this->em->getRepository(CommunicationMethods::class)->findAll();

        $select = '<select name="clinic_communication_methods_form[communicationMethod]" id="communication_methods_type" class="form-control">';

        foreach($communication_methods as $method){

            $select .= '<option value="'. $method->getId() .'">'. $method->getMethod() .'</option>';
        }

        $select .= '</select>';

        $response = '
        <div class="row" id="communication_methods">
            <div class="col-12 col-sm-6 mb-3 mt-5">
                <h3>Manage Communication Methods</h3>
            </div>
            <!-- Create New -->
            <div class="col-12 col-sm-6 mb-3 mt-5">
                <button type="button" class="btn btn-primary w-sm-100 float-end" data-bs-toggle="modal" data-bs-target="#modal_communication_methods" id="communication_methods_new">
                    <i class="fa-solid fa-circle-plus"></i> CREATE NEW COMMUNICATION METHOD
                </button>
            </div>
            <div class="col-12 mb-3 mt-2 info text-center text-sm-start">
                <p>
                    Add or remove communication methods from the list below.
                </p>';

        if($clinic_communication_methods == null){

            $response .= '<p><i>You do not currently have any communication methods created. Add a new communication method below</i></p>';
        }
        
        $response .= '
        <div class="row d-none d-md-flex">
            <div class="row">
                <div class="col-md-4 t-header">
                    Method
                </div>
                <div class="col-md-4 t-header">
                    Send To
                </div>
                <div class="col-md-4 t-header">
    
                </div>
            </div>
        
            <div id="communication_method_list" style="width: calc(100% - 24px)">';
        
            foreach($clinic_communication_methods as $method) {
    
                $response .= '
                <div class="row t-row">
                    <div class="col-md-4 t-cell text-truncate">
                        '. $method->getCommunicationMethod()->getMethod() .'
                    </div>
                    <div class="col-md-4 t-cell text-truncate">
                        '. $method->getSendTo() .'
                    </div>
                    <div class="col-md-4 t-cell text-truncate" id="">
                        <a href="" 
                            class="float-end communication_method_update" 
                            data-communication-method-id="'. $method->getCommunicationMethod()->getId() .'"
                            data-clinic-communication-method-id="'. $method->getId() .'"
                            data-bs-toggle="modal" 
                            data-bs-target="#modal_communication_methods"
                        >
                            <i class="fa-solid fa-pen-to-square edit-icon"></i>
                        </a>
                        <a 
                            href="" 
                            class="delete-icon float-end method-delete" 
                            data-bs-toggle="modal" data-clinic-communication-method-id="'. $method->getId() .'" 
                            data-bs-target="#modal_method_delete"
                        >
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    </div>
                </div>';
            }
        
            $response .= '
                    </div>
                </div>
            </div>
    
            <!-- Modal Manage Communication Methods -->
            <div class="modal fade" id="modal_communication_methods" tabindex="-1" aria-labelledby="communication_methods_modal_label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form name="form_communication_methods" id="form_communication_methods" method="post">
                            <input type="hidden" value="0" name="clinic_communication_methods_form[clinic_communication_method_id]" id="communication_method_id">
                            <div class="modal-header">
                                <h5 class="modal-title" id="communication_methods_modal_label">Create a Communication Method</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-12" id="col_communication_method">
                                        <label>Method</label>
                                        '. $select .'
                                        <div class="hidden_msg" id="error_communication_method">
                                            Required Field
                                        </div>
                                    </div>
        
                                    <div class="col-6" id="col_send_to">
                                        <label id="label_send_to">
                                        </label>
                                        <input 
                                            type="text" 
                                            name="clinic_communication_methods_form[sendTo]" 
                                            id="send_to"
                                            class="form-control"
                                        >
                                        <div class="hidden_msg" id="error_send_to">
                                            Required Field
                                        </div>
                                    </div>
        
                                </div>
        
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCEL</button>
                                <button type="submit" class="btn btn-primary">CREATE COMMUNICATION METHOD</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    
            <!-- Modal Delete Communication Method -->
            <div class="modal fade" id="modal_method_delete" tabindex="-1" aria-labelledby="method_delete_label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="method_delete_label">Delete Communication Method</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12 mb-0">
                                    Are you sure you would like to delete this communication method? This action cannot be undone.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">CANCEL</button>
                            <button 
                                type="button" 
                                class="btn btn-danger btn-sm communication-method-delete" 
                                id="delete_method">DELETE</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        return $response;
    }

    #[Route('/clinics/get-communication_methods', name: 'get_communication_methods')]
    public function getCommunicationMethodsAction(): Response
    {
        $response = $this->getCommunicationMethods();
        
        return new JsonResponse($response);
    }

    #[Route('/clinics/get-method', name: 'get_communication_method')]
    public function getMethodAction(Request $request): Response
    {

        $method = $this->em->getRepository(ClinicCommunicationMethods::class)->find($request->request->get('id'));

        $response = [

            'id' => $method->getId(),
            'method_id' => $method->getCommunicationMethod()->getId(),
            'method' => $method->getCommunicationMethod()->getMethod(),
            'send_to' => $method->getSendTo(),
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/communication-methods', name: 'communication_methods')]
    public function clinicCommunicationMethodsAction(Request $request): Response
    {
        $data = $request->request->get('clinic_communication_methods_form');
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $communication_method_repo = $this->em->getRepository(CommunicationMethods::class)->find($data['communicationMethod']);
        $method_id = (int) $data['clinic_communication_method_id'];

        if($data['clinic_communication_method_id'] == 0) {

            $clinic_communication_method = new ClinicCommunicationMethods();

        } else {

            $clinic_communication_method = $this->em->getRepository(ClinicCommunicationMethods::class)->find($method_id);
        }

        $clinic_communication_method->setClinic($clinic);
        $clinic_communication_method->setCommunicationMethod($communication_method_repo);
        $clinic_communication_method->setSendTo($data['sendTo']);
        $clinic_communication_method->setIsActive(1);

        $this->em->persist($clinic_communication_method);
        $this->em->flush();

        $communication_methods = $this->getCommunicationMethods();

        $response = [
            'flash' => '<b><i class="fas fa-check-circle"></i> Communication Method successfully created.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>',
            'communication_methods' => $communication_methods
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/method/delete', name: 'communication_method_delete')]
    public function clinicDeleteMethod(Request $request): Response
    {
        $method_id = $request->request->get('id');
        $method = $this->em->getRepository(ClinicCommunicationMethods::class)->find($method_id);

        $method->setIsActive(0);

        $this->em->persist($method);
        $this->em->flush();

        $communication_methods = $this->getCommunicationMethods();

        $flash = '<b><i class="fas fa-check-circle"></i> Communication method successfully deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        $response = [

            'flash' => $flash,
            'communication_methods' => $communication_methods,
        ];

        return new JsonResponse($response);
    }
}
