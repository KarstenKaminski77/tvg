<?php

namespace App\Controller;

use App\Entity\ClinicUsers;
use App\Entity\ProductReviews;
use App\Entity\Products;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductReviewsController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('clinics/create-review', name: 'create_review')]
    public function createReviewAction(Request $request): Response
    {
        $data = $request->request;
        $product = $this->em->getRepository(Products::class)->find((int) $data->get('review_product_id'));
        $user_name = $this->get('security.token_storage')->getToken()->getUser()->getUserIdentifier();
        $user = $this->em->getRepository(ClinicUsers::class)->findBy(['email' => $user_name]);
        $clinic = $this->get('security.token_storage')->getToken()->getUser()->getClinic();
        $review = new ProductReviews();

        $review->setClinicUser($user[0]);
        $review->setClinic($clinic->getClinicName());
        $review->setProduct($product);
        $review->setSubject($data->get('review_title'));
        $review->setReview($data->get('review'));
        $review->setRating($data->get('rating'));

        $this->em->persist($review);
        $this->em->flush();

        $user[0]->setUsername($data->get('review_username'));

        $this->em->persist($user[0]);
        $this->em->flush();

        $response = '<b><i class="fa-solid fa-circle-check"></i></i></b> Review Submitted.<div class="flash-close"><i class="fa-solid fa-xmark"></i></div>';

        return new JsonResponse($response);
    }

    #[Route('clinics/get-reviews/{product_id}', name: 'get_reviews')]
    public function getReviewsAction(Request $request): Response
    {
        $product_id = $request->get('product_id');

        $review = $this->em->getRepository(ProductReviews::class)->getAverageRating($product_id);

        $response = [
            'review_count' => $review[0][2],
            'review_average' => $review[0][1]
        ];

        return new JsonResponse($response);
    }

    #[Route('clinics/get-review-details/{product_id}', name: 'get_review_details')]
    public function getReviewDetailsAction(Request $request): Response
    {
        $product_id = $request->get('product_id');
        $product = $this->em->getRepository(Products::class)->find($product_id);
        $reviews = $this->em->getRepository(ProductReviews::class)->findBy(['product' => $product],['id' => 'DESC',2]);
        $response = '<h3 class="pb-3 pt-3">Reviews</h3><h5>Showing the 3 most popular reviews</h5>';

        foreach($reviews as $review){

            $response .= '<div class="row">
                            <div class="col-12">
                                <div id="review_detail_'. $review->getId() .'" class="mb-3 mt-2 d-inline-block">
                                    <i class="star star-under fa fa-star">
                                        <i class="star star-over fa fa-star star-visible"></i>
                                    </i>
                                    <i class="star star-under fa fa-star">
                                        <i class="star star-over fa fa-star star-visible"></i>
                                    </i>
                                    <i class="star star-under fa fa-star">
                                        <i class="star star-over fa fa-star star-visible"></i>
                                    </i>
                                    <i class="star star-under fa fa-star">
                                        <i class="star star-over fa fa-star star-visible"></i>
                                    </i>
                                    <i class="star star-under fa fa-star">
                                        <i class="star star-over fa fa-star star-visible" style="width: 33%;"></i>
                                    </i>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <h5>'. $product->getName() .'</h5>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">

                            </div>
                        </div>
                        
                        <script>
                            rateStyle('. $review->getRating() .', "review_detail_'. $review->getId() .'");
                        </script>';
        }

        return new JsonResponse($response);
    }
}
