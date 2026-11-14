<?php
class ServiceController extends Controller
{
    public function index()
    {
        $categoryId = $this->queryInt('category_id');
        $search = $this->queryString('search', 100);
        $model = $this->model('ServiceModel');
        $categories = $this->model('CategoryModel')->getActive();
        $categoryCounts = [];
        foreach ($categories as $category) {
            $categoryCounts[(int) $category['id']] = $model->countPublicList((int) $category['id'], $search);
        }
        $pagination = $this->paginationData($model->countPublicList($categoryId, $search));
        $this->view('service/index', [
            'title' => 'Dịch vụ',
            'categories' => $categories,
            'categoryCounts' => $categoryCounts,
            'services' => $model->getPublicList($categoryId, $search, $pagination['perPage'], $pagination['offset']),
            'categoryId' => $categoryId,
            'search' => $search,
        ] + $pagination);
    }
}
