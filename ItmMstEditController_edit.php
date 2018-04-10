<?php

declare(strict_types=1);
namespace App\Controller\Mst;

use App\Controller\Common\CommonControllerTrait;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\ProductType;
use App\Entity\ProductCategory;
use App\Form\Mst\ItmMstEditType;
use App\Form\Mst\ItmMstImageType;
use App\Repository\ProductRepository;
use App\Repository\ProductCategoryRepository;
use App\Service\ProductRegister;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

/**
 * ボディマスタ編集画面のコントローラー
 *
 * @Route("/mst/itmMst")
 */
class ItmMstEditController extends Controller
{
    use CommonControllerTrait;

    /** @var ProductRegister */
    private $register;

    /**
     * @required
     * @param ProductRegister $register
     */
    public function _setInjection(ProductRegister $register): void
    {
        $this->register = $register;
    }

    /**
     * @Route("/edit/id/{id}", name="app_itm_mst_edit")
     * @Route("/create", name="app_itm_mst_create", defaults={"id"=null})
     * @param Request $request
     * @param ProductRepository $pRepo
     * @param int $id
     * @return Response
     */
    public function edit(Request $request, ProductRepository $pRepo, ?int $id): Response
    {
        $this->em->beginTransaction();
        if ($request->get('delete')) {
            $filter = $request->get('filter', []);
            return $this->delete($pRepo, $id, $filter);
        }
        $isNew = !$id;
        $org = $this->getOrganization();
        $p = $isNew ? new Product() : $pRepo->findBodyById($id, $org);
        $filter = $request->get('filter', []);
        $form = $this->createForm(ItmMstEditType::class, $p);
        $form->handleRequest($request);
        if ($isNew) {
            $pi = new ProductImage();
        } else {
            $piList = $p->getProductImages();
            $pi = count($piList) ? $piList[0] : new ProductImage();
        }
        $imageForm = $this->createForm(ItmMstImageType::class, $pi);
        $imageForm->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->editSuccess($p, $pi, $filter);
        }

        return $this->render('Mst/itmMstEdit.html.twig', [
            'p' => $p,
            'pi' => $pi,
            'filter' => $filter,
            'form' => $form->createView(),
            'imageForm' => $imageForm->createView(),
        ]);
    }

    /**
     * @param ProductRepository $pRepo
     * @param int $id
     * @return Response
     */
    private function delete(ProductRepository $pRepo, int $id, $filter): Response
    {
        try{
            $org = $this->getOrganization();
            $wp = $pRepo->findBodyById($id, $org);
            $this->em->remove($wp);
            $this->em->flush();
            $this->em->commit();
            $this->addFlash('notice', '削除しました。');
        }catch (ForeignKeyConstraintViolationException $e){
            $this->addFlash('error', 'このデータは使用されているため削除できません。');
            return $this->redirect($this->generateUrl('app_itm_mst_edit', [
                'id' =>$id,
                'filter' => $filter,
            ]));
        }

        return $this->redirect($this->generateUrl('app_itm_mst_list', ['filter[]' => '']));
    }

    /**
     * @param Product $p
     * @param ProductImage $pi
     * @param array $filter
     * @return RedirectResponse
     */
    private function editSuccess(Product $p, ProductImage $pi, array $filter): RedirectResponse
    {
        $this->register->registerProduct($p, ProductType::ID_BODY);
        $this->register->registerProductImage($p, $pi);
        $this->em->persist($p);
        $this->em->flush();
        $this->em->commit();
        $this->addFlash('notice', '保存しました。');

        return $this->redirect($this->generateUrl('app_itm_mst_edit', [
            'id' => $p->getId(),
            'filter' => $filter,
        ]));
    }

    /**
     * @Route("/findCategory/id/{id}", name="app_itm_mst_find_category", options={"expose"=true})
     * @param Request $request
     * @param ProductCategoryRepository $pcRepo
     * @param int|null $id
     * @return JsonResponse
     */
    public function findProduct(Request $request, ProductCategoryRepository $pcRepo, ?int $id ): JsonResponse
    {
        $org = $this->getOrganization();
        /** @var Product $p */
        $pc = $pcRepo->findAnyTypeByIdEnabledPreset( $id, $org );
        if( ! $pc ){
            return new JsonResponse(['status' => false]);
        }
        return $this->json([
            'status'  => true,
            'productcategory' => $pc
        ], 200, [], ['groups' => ['productView']]);
    }
}
