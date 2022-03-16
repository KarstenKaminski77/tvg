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

        $user[0]->setReviewUsername($data->get('review_username'));

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
            'review_average' => number_format($review[0][1],1)
        ];

        return new JsonResponse($response);
    }

    #[Route('clinics/get-reviews-on-load/{product_id}', name: 'get_reviews_on_load')]
    public function getReviewsOnLoadAction(Request $request): Response
    {
        $product_id = $request->get('product_id');

        $review = $this->em->getRepository(ProductReviews::class)->getAverageRating($product_id);

        $response = '<div id="review_count_'. $product_id .'" class="d-inline-block">'. $review[0][2] .' Reviews</div>';
        $response .= "<script>rateStyle('". number_format($review[0][1],1) ."', 'parent_". $product_id ."');</script>";

        return new Response($response);
    }

    #[Route('clinics/get-review-details/{product_id}', name: 'get_review_details')]
    public function getReviewDetailsAction(Request $request): Response
    {
        $product_id = $request->get('product_id');
        $product = $this->em->getRepository(Products::class)->find($product_id);
        $reviews = $this->em->getRepository(ProductReviews::class)->findBy(['product' => $product],['id' => 'DESC'], 3);
        $rating_1 = $this->em->getRepository(ProductReviews::class)->getProductRating($product->getId(),1);
        $rating_2 = $this->em->getRepository(ProductReviews::class)->getProductRating($product->getId(),2);
        $rating_3 = $this->em->getRepository(ProductReviews::class)->getProductRating($product->getId(),3);
        $rating_4 = $this->em->getRepository(ProductReviews::class)->getProductRating($product->getId(),4);
        $rating_5 = $this->em->getRepository(ProductReviews::class)->getProductRating($product->getId(),5);
        $response = '<h3 class="pb-3 pt-3">Reviews</h3><h5 class="pb-4">Showing the 3 most recent reviews</h5>';

        if(empty($rating_1)){

            $rating_1[0]['total'] = 0;
        }

        if(empty($rating_2)){

            $rating_2[0]['total'] = 0;
        }

        if(empty($rating_3)){

            $rating_3[0]['total'] = 0;
        }

        if(empty($rating_4)){

            $rating_4[0]['total'] = 0;
        }

        if(empty($rating_5)){

            $rating_5[0]['total'] = 0;
        }

        $total = $rating_1[0]['total'] + $rating_2[0]['total'] + $rating_3[0]['total'] + $rating_4[0]['total'] + $rating_5[0]['total'];

        $star_1 = 0;
        $star_2 = 0;
        $star_3 = 0;
        $star_4 = 0;
        $star_5 = 0;

        if($rating_1[0]['total'] > 0){

            $star_1 = round($rating_1[0]['total'] / $total * 100);
        }

        if($rating_2[0]['total'] > 0){

            $star_2 = round($rating_2[0]['total'] / $total * 100);
        }

        if($rating_3[0]['total'] > 0){

            $star_3 = round($rating_3[0]['total'] / $total * 100);
        }

        if($rating_4[0]['total'] > 0){

            $star_4 = round($rating_4[0]['total'] / $total * 100);
        }

        if($rating_5[0]['total'] > 0){

            $star_5 = round($rating_5[0]['total'] / $total * 100);
        }

        if($reviews != null) {

            $response .= '<div class="row">
                                <div class="col-12 col-sm-6 text-center">
                                    <div class="star-raiting-container">
                                        <div class="star-rating-col-sm info">
                                            5 Star
                                        </div>
                                        <div class="star-rating-col-lg info">
                                            <div class="progress-outer">
                                                <div class="progress-inner" style="width: '. $star_5 .'%;"></div>
                                            </div>
                                        </div>
                                        <div class="star-rating-col-sm info text-start">
                                            '. $star_5 .'%
                                        </div>
                                    </div>
                                    <div class="star-raiting-container">
                                        <div class="star-rating-col-sm info">
                                            4 Star
                                        </div>
                                        <div class="star-rating-col-lg info">
                                            <div class="progress-outer">
                                                <div class="progress-inner" style="width: '. $star_4 .'%;"></div>
                                            </div>
                                        </div>
                                        <div class="star-rating-col-sm info text-start">
                                            '. $star_4 .'%
                                        </div>
                                    </div>
                                    <div class="star-raiting-container">
                                        <div class="star-rating-col-sm info">
                                            3 Star
                                        </div>
                                        <div class="star-rating-col-lg info">
                                            <div class="progress-outer">
                                                <div class="progress-inner" style="width: '. $star_3 .'%;"></div>
                                            </div>
                                        </div>
                                        <div class="star-rating-col-sm info text-start">
                                            '. $star_3 .'%
                                        </div>
                                    </div>
                                    <div class="star-raiting-container">
                                        <div class="star-rating-col-sm info">
                                            2 Star
                                        </div>
                                        <div class="star-rating-col-lg info">
                                            <div class="progress-outer">
                                                <div class="progress-inner" style="width: '. $star_2 .'%;"></div>
                                            </div>
                                        </div>
                                        <div class="star-rating-col-sm info text-start">
                                            '. $star_2 .'%
                                        </div>
                                    </div>
                                    <div class="star-raiting-container">
                                        <div class="star-rating-col-sm info">
                                            1 Star
                                        </div>
                                        <div class="star-rating-col-lg info">
                                            <div class="progress-outer">
                                                <div class="progress-inner" style="width: '. $star_1 .'%;"></div>
                                            </div>
                                        </div>
                                        <div class="star-rating-col-sm info text-start">
                                            '. $star_1 .'%
                                        </div>
                                    </div>
                                        </div>
                                        <div class="col-12 col-sm-6 text-center pt-4 pb-4 pt-sm-0 pb-sm-0">
                                            <h6>Help other Fluid clinics</h6>
                                            <p>Let thousands of veterinary purchasers know about<br> your experience with this product</p>
                                            <a href="" class="btn btn-primary btn_create_review w-100 w-sm-100" data-bs-toggle="modal" data-product-id="'. $product_id .'" data-bs-target="#modal_review">
                                                WRITE A REVIEW
                                            </a>
                                        </div>
                                    </div>';

            foreach ($reviews as $review) {

                $response .= '<div class="row">
                            <div class="col-12">
                                <div class="mb-3 mt-2 d-inline-block">
                ';

                for($i = 0; $i < $review->getRating(); $i++){

                    $response .= '<i class="star star-over fa fa-star star-visible position-relative start-sm-over"></i>';
                }

                for($i = 0; $i < (5 - $review->getRating()); $i++) {

                    $response .= '<i class="star star-under fa fa-star"></i>';
                }

                $response .='    </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-2">
                            <h5>'. $review->getSubject() .'</h5>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-2">
                            Written on '. $review->getCreated()->format('d M Y') .' by <b>'. $review->getClinicUser()->getReviewUsername() .', '. $review->getPosition() .'</b>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <p>' . $review->getReview() . '</p>
                        </div>
                    </div>';
            }
        } else {

            $response = false;
        }

        return new JsonResponse($response);
    }
}
