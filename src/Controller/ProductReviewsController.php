<?php

namespace App\Controller;

use App\Entity\ClinicUsers;
use App\Entity\ProductReviewComments;
use App\Entity\ProductReviewLikes;
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
        if($request->request->get('product_id') == null) {

            $product_id = $request->get('product_id');
            $limit = 3;

        } else {

            $product_id = $request->get('product_id');
            $limit = 100;
        }
        $product_review = $this->em->getRepository(ProductReviews::class)->findBy([
            'product' => $product_id,
            'clinicUser' => $this->getUser()->getId()
        ]);
        $product = $this->em->getRepository(Products::class)->find($product_id);
        $reviews = $this->em->getRepository(ProductReviews::class)->findBy(['product' => $product],['id' => 'DESC'], $limit);
        $rating_1 = $this->em->getRepository(ProductReviews::class)->getProductRating($product->getId(),1);
        $rating_2 = $this->em->getRepository(ProductReviews::class)->getProductRating($product->getId(),2);
        $rating_3 = $this->em->getRepository(ProductReviews::class)->getProductRating($product->getId(),3);
        $rating_4 = $this->em->getRepository(ProductReviews::class)->getProductRating($product->getId(),4);
        $rating_5 = $this->em->getRepository(ProductReviews::class)->getProductRating($product->getId(),5);
        $response = '<h3 class="pb-3 pt-3">Reviews</h3><h5 class="pb-4 recent-reviews">Showing the 3 most recent reviews</h5>';
        $write_review = '';

        if($product_review != null){

            $write_review = 'btn-secondary disabled';
        }

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

            $response .= '
            <div class="row">
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
                    <a 
                        href="" 
                        class="btn btn-primary btn_create_review w-100 w-sm-100 '. $write_review .'" 
                        data-bs-toggle="modal" data-product-id="'. $product_id .'" 
                        data-bs-target="#modal_review">
                        WRITE A REVIEW
                    </a>
                </div>
            </div>';

            $c = 0;

            foreach ($reviews as $review) {

                $c++;

                $product_review_comments = $this->em->getRepository(ProductReviewComments::class)->findBy([
                    'review' => $review->getId()
                ]);
                $product_review_likes = $this->em->getRepository(ProductReviewLikes::class)->findBy([
                    'productReview' => $review->getId(),
                    'clinicUser' => $this->getUser()->getId(),
                ]);

                if(count($product_review_likes) == 1){

                    $like_icon = 'text-secondary';

                } else {

                    $like_icon = 'list-icon-unchecked';
                }

                $like_count = $this->em->getRepository(ProductReviewLikes::class)->findBy([
                    'productReview' => $review->getId()
                ]);

                $response .= '
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3 mt-2 d-inline-block">
                ';

                for($i = 0; $i < $review->getRating(); $i++){

                    $response .= '<i class="star star-over fa fa-star star-visible position-relative start-sm-over"></i>';
                }

                for($i = 0; $i < (5 - $review->getRating()); $i++) {

                    $response .= '<i class="star star-under fa fa-star"></i>';
                }

                $comment_count = '';

                if(count($product_review_comments) > 0){

                    $comment_count = ' ('. count($product_review_comments) .')';
                }

                $view_all_reviews = '';

                if(count($reviews) == $c){

                    $view_all_reviews = '
                    <button 
                        class="btn btn-sm btn-light float-end info btn-view-all-reviews"
                        data-product-id="'. $product_id .'"
                    >
                        View All Reviews
                    </button>';
                }

                $response .='    
                </div>
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
                    </div>
                    <div class="row">
                        <div class="col-12 review-comments-row">
                            <button 
                                class="btn btn-sm btn-light review-like me-3 '. $like_icon .'" 
                                id="like_'. $review->getId() .'" 
                                data-review-id="'. $review->getId() .'"
                            >
                                <i class="fa-solid fa-thumbs-up review-icon me-2 '. $like_icon .'"></i> '. count($like_count) .'
                            </button>
                            <button 
                                class="btn btn-sm btn-light btn-comment me-3" 
                                data-review-id="'. $review->getId() .'"
                                id="btn_comment_'. $review->getId() .'"
                            >
                                <i 
                                    class="fa-solid fa-comment review-icon review-icon me-2 list-icon-unchecked"
                                     id="comment_icon_'. $review->getId() .'"
                                ></i> 
                                <span class="list-icon-unchecked" id="comment_span_'. $review->getId() .'">
                                    <span class="d-none d-sm-inline">Comments </span>'. $comment_count .'
                                </span>
                            </button>
                            '. $view_all_reviews .'
                        </div>
                    </div>
                    <div class="row comment-container hidden" id="comment_container_'. $review->getId() .'">
                        <div class="col-12">
                            <div class="mb-5">
                                <form name="form-comment" class="form-comment" data-review-id="'. $review->getId() .'" method="post">
                                    <input type="hidden" name="review_id" value="'. $review->getId() .'">
                                    <div class="row">
                                        <div class="col-12 col-sm-10">
                                            <input 
                                                type="text" 
                                                name="comment"
                                                id="comment_'. $review->getId() .'"
                                                class="form-control d-inline-block" 
                                                placeholder="Leave a comment on this review..."
                                            >
                                            <div class="hidden_msg" id="error_comment_'. $review->getId() .'">
                                                Required Field
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-2">
                                            <button 
                                                type="submit" 
                                                class="btn btn-primary d-inline-block" 
                                                data-review-id="'. $review->getId() .'">
                                                COMMENT
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12" id="review_comments_'. $review->getId() .'">';

                                        if(count($product_review_comments) > 0) {

                                            foreach ($product_review_comments as $comment) {

                                                $response .= '
                                                <div class="row mt-4">
                                                    <div class="col-12">
                                                        <b>' . $comment->getClinic()->getClinicUsers()[0]->getReviewUsername() . '</b> 
                                                        ' . $comment->getClinic()->getClinicUsers()[0]->getPosition() . ' '. $comment->getCreated()->format('dS M Y H:i') .'
                                                    </div>
                                                    <div class="col-12">
                                                        ' . $comment->getComment() . '
                                                    </div>
                                                </div>';
                                            }
                                        }

                                        $response .= '
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
            }
        } else {

            $response = false;
        }

        if($product->getForm() == 'Each'){

            $dosage = $product->getSize() . $product->getUnit();

        } else {

            $dosage = $product->getDosage() . $product->getUnit();
        }

        $json = [
            'response' => $response,
            'product_name' => $product->getName() .' '. $dosage,
        ];

        return new JsonResponse($json);
    }

    #[Route('clinics/like-review', name: 'like_review')]
    public function likeReviewAction(Request $request): Response
    {
        $data = $request->request;
        $review_id = $data->get('review_id');

        $user = $this->getUser()->getId();
        $product_review = $this->em->getRepository(ProductReviews::class)->find($review_id);
        $product_review_likes = $this->em->getRepository(ProductReviewLikes::class)->findBy([
            'productReview' => $review_id,
            'clinicUser' => $user
        ]);
        $prc = $product_review_likes;

        if(count($product_review_likes) == 0){

            $product_review_likes = new ProductReviewLikes();

            $product_review_likes->setClinicUser($this->getUser());
            $product_review_likes->setProductReview($product_review);

            $this->em->persist($product_review_likes);

            $response = '<i class="fa-solid fa-thumbs-up text-secondary review-icon me-2"></i>';

        } else {

            $product_review_likes = $this->em->getRepository(ProductReviewLikes::class)->find($product_review_likes[0]->getId());
            $this->em->remove($product_review_likes);

            $response = '<i class="fa-solid fa-thumbs-up list-icon-unchecked review-icon me-2"></i>';
        }

        $this->em->flush();

        $like_count = $this->em->getRepository(ProductReviewLikes::class)->findBy([
            'productReview' => $review_id
        ]);

        if(count($prc) == 0){

            $response .= '<span class="text-secondary">'. (int) count($like_count) .'</span>';

        } else {

            $response .= '<span class="list-icon-unchecked">'. (int) count($like_count) .'</span>';
        }

        return new JsonResponse($response);
    }

    #[Route('/clinics/manage-comment', name: 'inventory_manage_comment')]
    public function clinicsManageCommentAction(Request $request): Response
    {
        $data = $request->request;
        $review_id = $data->get('review_id');

        if($review_id > 0) {

            $review = $this->em->getRepository(ProductReviews::class)->find($review_id);
            $review_comment = new ProductReviewComments();

            $review_comment->setClinicUser($this->getUser());
            $review_comment->setClinic($this->getUser()->getClinic());
            $review_comment->setReview($review);
            $review_comment->setComment($data->get('comment'));

            $this->em->persist($review_comment);
            $this->em->flush();
        }

        $review_comments = $this->em->getRepository(ProductReviewComments::class)->findBy([
            'review' => $review_id
        ]);

        $response = '';

        if(count($review_comments) > 0) {

            foreach ($review_comments as $comment) {

                $response .= '
                <div class="row mt-4">
                    <div class="col-12">
                        <b>' . $comment->getClinic()->getClinicUsers()[0]->getReviewUsername() . '</b> 
                        ' . $comment->getClinic()->getClinicUsers()[0]->getPosition() . ' '. $comment->getCreated()->format('dS M Y H:i') .'
                    </div>
                    <div class="col-12">
                        ' . $comment->getComment() . '
                    </div>
                </div>';
            }
        }

        return new JsonResponse($response);
    }

    #[Route('/clinics/get-comment-count', name: 'get_comment_count')]
    public function clinicsGetCommentCountAction(Request $request): Response
    {
        $data = $request->request;
        $review_id = $data->get('review_id');
        $response = '';

        if($review_id > 0) {

            $review_comments = $this->em->getRepository(ProductReviewComments::class)->findBy([
                'review' => $review_id
            ]);

            if(count($review_comments) > 0) {

                $response = ' ('. count($review_comments) .')';
            }
        }

        return new JsonResponse($response);
    }
}
