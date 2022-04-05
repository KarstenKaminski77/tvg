<?php

namespace App\Controller;

use App\Entity\AvailabilityTracker;
use App\Entity\ClinicCommunicationMethods;
use App\Entity\DistributorProducts;
use App\Entity\Distributors;
use App\Entity\Products;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AvailabilityTrackerController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('/clinics/get-availability-tracker', name: 'get_availability_tracker')]
    public function getAvailabilityTrackerAction(Request $request): Response
    {
        $data = $request->request;
        $clinic_id = $this->getUser()->getClinic()->getId();
        $product_id = $data->get('product_id');

        $products = $this->em->getRepository(DistributorProducts::class)->findBy([
            'product' => $product_id,
            'stockCount' => 0
        ]);
        $communication_methods = $this->em->getRepository(ClinicCommunicationMethods::class)->findBy([
            'clinic' => $clinic_id,
            'isActive' => 1
        ]);

        $saved_trackers = $this->em->getRepository(AvailabilityTracker::class)->getSavedTrackers($product_id,$clinic_id);
        $distributors = '';

        if(count($saved_trackers) > 0){

            foreach($saved_trackers as $tracker){

                $distributors .= $tracker->getDistributor()->getId() .',';
            }

            $distributors = trim($distributors,',');
        }

        $html = '
        <form id="form_availability_tracker" name="form_availability_tracker" method="post">
            <input type="hidden" name="product_id" value="'. $product_id .'">
            <input type="hidden" name="availability_tracker_id" value="0">
            <h3 class="pb-3 pt-3">Availability Tracker</h3>';
        $html .= '
        <div class="row">
            <div class="col-12">
                Create custom alerts when a backordered item comes back in stock. Set a notification
                for how you would like to be notified and which suppliers you would like to track.
                Once an item comes back in stock and you are notified, the tracker will automatically
                turn off. You can also view a list of all tracked items in your shopping list.
                Note: Fluid cannot track the availability of items that are drop shipped directly
                from the vendor.
            </div>
        </div>';

        if(count($products) > 0) {

            $html .= '
            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary pt-3 pb-3">How Would You Like To Be Notified?</h6>';

            if(count($communication_methods) > 0){

                $i = 0;

                $html .= '<div class="row">';

                foreach($communication_methods as $method){

                    $i++;

                    if($method->getCommunicationMethod()->getId() == 1){

                        $notification = $method->getCommunicationMethod()->getMethod();

                    } else {

                        $notification = $method->getSendTo();
                    }

                    $html .= '
                    <div class="col-2">
                        <input 
                            type="checkbox" 
                            value="'. $method->getId() .'"
                            class="btn-check" 
                            name="method[]" 
                            id="method_'. $i .'" 
                            autocomplete="off"
                        >
                        <label class="btn btn-sm btn-outline-primary w-100 text-truncate" for="method_'. $i .'">
                            '. $notification .'
                        </label>
                    </div>';

                    if($i % 6 == 0 && $i != count($communication_methods)){

                        $html .= '
                        </div>
                        <div class="row mb-4">';
                    }
                }

                $html .= '
                </div>
                <div class="row">
                    <div class="col-12 hidden_msg" id="error_at_methods">
                        Please select at least one communication method.
                    </div>
                </div>';


            } else {

                $html .= '
                <button type="button" class="btn btn-primary">
                    <i class="fa-solid fa-circle-plus me-2"></i>
                    CREATE NEW COMMUNICATION METHOD
                </button>
                ';
            }

            $html .= '<h6 class="text-primary pt-3 mt-4 pb-3">Which Suppliers Would You Like To Track?</h6>';
            $html .= '<div class="row">';
            $i = 0;

            foreach($products as $product) {

                $i++;

                $html .= '
                <div class="col-2">
                    <input type="checkbox" class="btn-check" name="distributor[]" value="'. $product->getDistributor()->getId() .'" id="btn_distributor_'. $i .'" autocomplete="off">
                    <label class="btn btn-sm btn-outline-primary w-100 text-truncate" for="btn_distributor_'. $i .'">
                        '. $product->getDistributor()->getDistributorName() .'
                    </label>
                </div>';

                if($i % 6 == 0){

                    $html .= '
                        </div>
                        <div class="row mb-4">';
                }
            }

            $html .= '
            </div>
            <div class="row">
                    <div class="col-12 hidden_msg" id="error_at_distributors">
                        Please select at least one distributor.
                    </div>
                </div>';

            $html .= '
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <input type="submit" class="btn btn-primary float-end" value="ENABLE AVAILABILITY TRACKING">
                </div>
            </div>
            <div class="row mt-3 hidden" id="availability_tracker_row">
                <div class="col-12" id="availability_tracker_col">
                
                </div>
            </div>';

        } else {

            $html .= '
            <div class="row">
                <div class="col-12 mt-4 text-center">
                    There are no items to track. Tracking is only available for items that are currently out of stock.
                </div>
            </div>';
        }

        $html .= '</form>';

        $response = [
            'html' => $html,
            'list' => $this->getAvailabilityTrackers($product_id),
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/create-availability-tracker', name: 'create_availability_tracker')]
    public function createAvailabilityTrackerAction(Request $request): Response
    {
        $data = $request->request;
        $methods = $data->get('method');
        $distributors = $data->get('distributor');
        $product_id = $data->get('product_id');
        $clinic = $this->getUser()->getClinic();
        $product = $this->em->getRepository(Products::class)->find($product_id);

        if($data->get('availability_tracker_id') == 0){

            foreach($distributors as $distributor){

                $distributor_obj = $this->em->getRepository(Distributors::class)->find($distributor);

                foreach($methods as $method){

                    $availablility_tracker = new AvailabilityTracker();

                    $communication_method = $this->em->getRepository(ClinicCommunicationMethods::class)->find($method);

                    $availablility_tracker->setClinic($clinic);
                    $availablility_tracker->setProduct($product);
                    $availablility_tracker->setDistributor($distributor_obj);
                    $availablility_tracker->setCommunication($communication_method);
                    $availablility_tracker->setIsSent(0);

                    $this->em->persist($availablility_tracker);
                    $this->em->flush();
                }
            }

            $flash = $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> Availability tracker create.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        } else {

            //$availablility_tracker = $this->em->getRepository(AvailabilityTracker::class);

        }

        $response = [
            'flash' => $flash,
            'list' => $this->getAvailabilityTrackers($product_id)
        ];

        return new JsonResponse($response);
    }

    #[Route('/clinics/delete-availability-tracker', name: 'delete_availability_tracker')]
    public function deleteAvailabilityTrackerAction(Request $request): Response
    {
        $tracker_id = $request->request->get('tracker_id');
        $tracker = $this->em->getRepository(AvailabilityTracker::class)->find($tracker_id);
        $product_id = $tracker->getProduct()->getId();
        $flash = '';

        if($tracker != null){

            $this->em->remove($tracker);
            $this->em->flush();

            $flash = '<b><i class="fa-solid fa-circle-check"></i></i></b> Availability tracker deleted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';
        }

        $html = $this->getAvailabilityTrackers($product_id);

        $response = [
            'flash' => $flash,
            'html' => $html,
        ];

        return new JsonResponse($response);
    }

    private function getAvailabilityTrackers($product_id)
    {
        $clinic = $this->getUser()->getClinic();
        $saved_trackers = $this->em->getRepository(AvailabilityTracker::class)->findBy([
            'product' => $product_id,
            'clinic' => $clinic->getId()
        ]);

        $response = '';

        if(count($saved_trackers) > 0) {

            $response .= '
            <div id="availability_trackers">
                <div class="row d-none d-xl-flex ms-1 me-1 ms-md-0 me-md-0 mt-5">
                    <div class="col-4 t-header">
                        Communication Method
                    </div>
                    <div class="col-3 t-header">
                        Distributor
                    </div>
                    <div class="col-3 t-header">
                        Send To
                    </div>
                    <div class="col-2 t-header">
        
                    </div>
                </div>';

            $i = 0;

            foreach ($saved_trackers as $tracker) {

                $border_top = '';
                $i++;

                if ($i == 1) {

                    $border_top = 'style="border-top: 1px solid #d3d3d4"';
                }

                $col = 3;
                $send_to = $tracker->getCommunication()->getCommunicationMethod()->getClinicCommunicationMethods()[0]->getSendTo();
                $communication_method = $tracker->getCommunication()->getId();

                if (empty($send_to)) {

                    $col = 6;
                }

                if ($communication_method == 1) {

                    $send_to = '';
                }

                $response .= '
                <div class="row t-row ms-1 me-1 ms-md-0 me-md-0"  ' . $border_top . '>
                    <div class="col-4 col-sm-2 d-xl-none t-cell fw-bold text-primary border-list text-truncate">Method</div>
                    <div class="col-8 col-sm-10 col-xl-4 t-cell text-truncate border-list">
                        ' . $tracker->getCommunication()->getCommunicationMethod()->getMethod() . '
                    </div>
                    <div class="col-4 col-sm-2 d-xl-none t-cell fw-bold text-primary border-list text-truncate">Distributor</div>
                    <div class="col-8 col-sm-10 col-xl-' . $col . ' t-cell text-truncate border-list">
                        ' . $tracker->getDistributor()->getDistributorName() . '
                    </div>';

                if (!empty($send_to)) {

                    $response .= '
                    <div class="col-4 col-sm-2 d-xl-none t-cell fw-bold text-primary border-list text-truncate">Send To</div>
                    <div class="col-8 col-sm-10 col-xl-3 t-cell text-truncate border-list">
                        ' . $send_to . '
                    </div>';
                }

                $response .= '
                    <div class="col-12 col-xl-2 t-cell text-truncate">
                        <a 
                            href="" 
                            class="delete-icon float-start float-sm-end availability-tracker-delete-icon" 
                            data-bs-toggle="modal" data-availability-tracker-id="' . $tracker->getId() . '" 
                            data-bs-target="#modal_availability_tracker_delete"
                        >
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    </div>
                </div>';
            }

            $response .= '
                        </div>
                    </div>
            
                    <!-- Modal Delete Availability Tracker -->
                    <div 
                        class="modal fade" 
                        id="modal_availability_tracker_delete" 
                        tabindex="-1" 
                        aria-labelledby="availability_tracker_delete_label" 
                        aria-hidden="true"
                    >
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="availability_tracker_delete_label">Delete Availability Tracker</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-12 mb-0">
                                            Are you sure you would like to delete this availability tracker? This action cannot be undone.
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">CANCEL</button>
                                    <button 
                                        type="button" 
                                        class="btn btn-danger btn-sm communication-method-delete" 
                                        id="delete_tracker">DELETE</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
        }

        return $response;
    }
}
