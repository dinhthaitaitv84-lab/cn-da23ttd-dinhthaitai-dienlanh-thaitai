<?php
class HomeController extends Controller
{
    public function index()
    {
        $homeModel = $this->model('HomeModel');
        $data = [
            'title' => 'Dịch vụ sửa chữa điện, điện lạnh tại nhà',
            'categories' => $homeModel->getCategories(),
            'categoryServices' => $this->model('ServiceModel')->getPublicList(),
            'popularServices' => $homeModel->getPopularServices(),
            'statistics' => $homeModel->getStatistics(),
            'recentReviews' => $homeModel->getRecentReviews(),
        ];

        $this->view('home/index', $data);
    }
}