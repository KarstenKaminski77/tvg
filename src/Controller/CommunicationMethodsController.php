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
        $clinic_communication_methods = $this->em->getRepository(ClinicCommunicationMethods::class)->findByClinic($clinic->getId());

        $communication_methods = $this->em->getRepository(CommunicationMethods::class)->findByNotInApp();

        $select = '<select name="clinic_communication_methods_form[communicationMethod]" id="communication_methods_type" class="form-control">';
        $select .= '<option value="">Please Select a Communication Method</option>';

        foreach($communication_methods as $method){

            $select .= '<option value="'. $method->getId() .'">'. $method->getMethod() .'</option>';
        }

        $select .= '</select>';

        $response = '
        <div class="row" id="communication_methods">
            <!-- Create New -->
            <div class="col-12 col-md-12 mb-3 mt-0">
                <button type="button" class="btn btn-primary w-sm-100 float-end" data-bs-toggle="modal" data-bs-target="#modal_communication_methods" id="communication_methods_new">
                    <i class="fa-solid fa-circle-plus"></i> CREATE NEW COMMUNICATION METHOD
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-12 bg-primary bg-gradient text-center pt-3 pb-3 mt-4" id="order_header">
                <h3 class="text-light">Manage Communication Methods</h3>
                <span class="mb-5 mt-2 text-center text-light text-sm-start">
                    Add or remove communication methods from the list below.
                </span>
            </div>
        </div>';

        if($clinic_communication_methods == null) {

            $response .= '
            <div class="row">
                <div class="col-12 pb-5 pt-2 info text-center text-sm-start">
                    <p class="mb-0">
                        Add or remove communication methods from the list below.
                    </p>
                </div>
            </div>';
        }
        
        $response .= '
        <div class="row d-none d-xl-flex  bg-light border-bottom border-right border-left">
            <div class="col-5 pt-3 pb-3 text-primary fw-bold">
                Method
            </div>
            <div class="col-5 pt-3 pb-3 text-primary fw-bold">
                Send To
            </div>
            <div class="col-2 pt-3 pb-3 text-primary fw-bold">

            </div>
        </div>';

        $i = 0;
        
        foreach($clinic_communication_methods as $method) {

            $mobile_no = 0;
            $i++;

            $col = 10;

            if(!empty($method->getSendTo())) {

                $col = 5;
            }

            if($method->getCommunicationMethod()->getId() == 3){

                $mobile_no = $method->getSendTo();

            } else {

                $mobile_no = 0;
            }

            $response .= '
            <div class="row t-row">
                <div class="col-4 col-sm-2 d-xl-none  t-cell text-truncate border-list pt-3 pb-3">Method</div>
                <div class="col-8 col-sm-10 col-xl-'. $col .'  t-cell text-truncate border-list pt-3 pb-3">
                    '. $method->getCommunicationMethod()->getMethod() .'
                </div>';

            if(!empty($method->getSendTo())) {

                $response .= '
                <div class="col-4 col-sm-2 d-xl-none  t-cell text-truncate border-list pt-3 pb-3">Send To</div>
                <div class="col-8 col-sm-10 col-xl-5  t-cell text-truncate border-list pt-3 pb-3">
                    ' . $method->getSendTo() . '
                </div>';
            }

            $response .= '
                <div class="col-12 col-xl-2 t-cell text-truncate pt-3 pb-3" id="">
                    <a href="" 
                        class="float-end communication_method_update" 
                        data-communication-method-id="' . $method->getCommunicationMethod()->getId() . '"
                        data-clinic-communication-method-id="' . $method->getId() . '"
                        data-mobile-no="'. $mobile_no .'"
                        data-bs-toggle="modal" 
                        data-bs-target="#modal_communication_methods"
                    >
                        <i class="fa-solid fa-pen-to-square edit-icon"></i>
                    </a>
                    <a 
                        href="" 
                        class="delete-icon float-start float-sm-end method-delete" 
                        data-bs-toggle="modal" data-clinic-communication-method-id="' . $method->getId() . '" 
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
    
            <!-- Modal Manage Communication Methods -->
            <div class="modal fade" id="modal_communication_methods" tabindex="-1" aria-labelledby="communication_methods_modal_label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form name="form_communication_methods" id="form_communication_methods" method="post">
                            <input type="hidden" value="0" name="clinic_communication_methods_form[clinic_communication_method_id]" id="communication_method_id">
                            <input type="hidden" value="0" name="clinic_communication_methods_form[mobile]" id="mobile_no">
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
                                        <span id="send_to_container">
                                        <input 
                                            type="text" 
                                            name="clinic_communication_methods_form[sendTo]" 
                                            id="send_to"
                                            class="form-control"
                                        >
                                        </span>
                                        <div class="hidden_msg" id="error_send_to">
                                            Required Field
                                        </div>
                                        <div class="hidden_msg" id="error_communication_method_mobile">
                                            Invalid Number
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

        // If mobile remove intl dialing code
        if($method->getCommunicationMethod()->getId() == 3){

            $offset = strlen($method->getIntlCode());

            $send_to = substr($method->getSendTo(), $offset);

        } else {

            $send_to = $method->getSendTo();
        }

        $response = [

            'id' => $method->getId(),
            'method_id' => $method->getCommunicationMethod()->getId(),
            'method' => $method->getCommunicationMethod()->getMethod(),
            'send_to' => $send_to,
            'iso_code' => $method->getIsoCode(),
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/manage-communication-methods', name: 'manage_communication_methods')]
    public function manageCommunicationMethodsAction(Request $request): Response
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
        $clinic_communication_method->setIsDefault(0);

        if((int) $data['mobile'] == 0) {

            $clinic_communication_method->setSendTo($data['sendTo']);

        } else {

            $clinic_communication_method->setSendTo($data['mobile']);
            $clinic_communication_method->setIsoCode($data['iso_code']);
            $clinic_communication_method->setIntlCode($data['intl_code']);
        }

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
