<?php

namespace Controller;

use Core\FlashMessage;
use Model\Article;
use Model\Receipt;
use Model\User;
use Core\IoC;
use Repository\ArticleFileRepository;

class ArticleController
{
    public function index($page){

        /** @var User $user */
        $user = getCurrentUser();

        if($user == null) {
            header('Location: /?r=/auth');
        }
        $articleSet = IoC::resolve('diaryStore')->getArticlesByUserId($user->getId());
        $articles = [];

        $pages = ceil(count($articleSet) / 5);

        for($i = 0; $i < 5; $i++) {
            if(count($articleSet) - $i - 1 - 5 * ($page - 1) >= 0)
                $articles[$i] = $articleSet[count($articleSet) - $i - 1 - 5 * ($page - 1)];
        }

        $next = floor(($page-1)/4);

        $isOwner = true;

        view()->render('article_list', ['articles'=>$articles , 'pages' => $pages, 'next' => $next, 'isOwner' => $isOwner]);
    }

    public function write() {

        /** @var User $user */
        $user = getCurrentUser();

        if($user == null) {
            header('Location: /?r=/auth');
        }

        view()->render('article_create');
    }

    public function create(){

        /** @var \Core\Message\FlashMessage $message */
        $message = IoC::resolve('message');

        if(strlen(trim($_POST["subject"])) < 10) {
            $message->error('제목을 10자 이상 입력해주세요.');
            return redirectBack();
        }
        if(strlen(trim($_POST["content"])) < 10) {
            $message->error('내용을 10자 이상 입력해주세요.');
            return redirectBack();
        }
        
        $article = new Article(getCurrentUser(),$_POST["date"],$_POST["subject"],$_POST["content"],$_POST["secret"], $_POST['weather']);
        for($i = 0; $i < count($_POST["price"]); $i++) {

            $price = $_POST["price"][$i];
            $summary = $_POST["summary"][$i];
            $currency = $_POST["currency"][$i];

            if(!is_numeric($price)) continue;

            $article->addReceipt(new Receipt($summary,$price,$currency));
        }
        
        /** @var ArticleFileRepository $repository */
        $repository = IoC::resolve('diaryStore');
        $repository->createArticle($article);

        header('Location: /?r=/article/'.$article->getId());
    }

    public function update($id){

        /** @var Article $article */
        $article = IoC::resolve('diaryStore')->getArticleById($id);

        $article->setDate($_POST["date"]);
        $article->setSubject($_POST["subject"]);
        $article->setContent($_POST["content"]);
        $article->setWeather($_POST["weather"]);

        if($_POST["check"] =="on")
            $article->setSecret($_POST["secret"]);
        else
            $article->setSecret("");

        $article->deleteAllReceipt();

        for($i = 0; $i < count($_POST["price"]); $i++) {

            $price = $_POST["price"][$i];
            $summary = $_POST["summary"][$i];
            $currency = $_POST["currency"][$i];

            if(!is_numeric($price)) continue;

            $article->addReceipt(new Receipt($summary, $price, $currency));
        }


        IoC::resolve('diaryStore')->modifyArticle($article);

        header('Location: /?r=/article/'.$article->getId());
    }

    public function modify($id){
        $article = IoC::resolve('diaryStore')->getArticleById($id);

        if(getCurrentUser()->getId() !== $article->getUser()->getId())
        {
            view()->render('not_authorized', ['article'=>$article]);
            return;
        }
        view()->render('article_modify', ['article'=>$article]);
    }

    public function view($id){

        /** @var User $currentUser */
        $currentUser = getCurrentUser();

        $article = IoC::resolve('diaryStore')->getArticleById($id);
        $isOwner = false;
        if(isset($currentUser) && $article->getUser()->getId() == $currentUser->getId()){
            $isOwner = true;
        }
        view()->render('article', ['article'=>$article, 'isOwner'=>$isOwner]);
    }

    public function remove($id){
        IoC::resolve('diaryStore')->removeArticleById($id);

        header('Location: /?r=/articles/my/1');
    }

    public function userArticles($userId, $page) {
        if(getCurrentUser() == null){
            header('Location: /?r=/auth');
        }

        $articleSet = IoC::resolve('diaryStore')->getArticlesByUserId($userId);
        $articles = [];

        $pages = ceil(count($articleSet) / 5);

        for($i = 0; $i < 5; $i++) {
            if(count($articleSet) - $i - 1 - 5 * ($page - 1) >= 0)
                $articles[$i] = $articleSet[count($articleSet) - $i - 1 - 5 * ($page - 1)];
        }

        $next = floor(($page-1)/4);

        if($userId == getCurrentUser()->getId()){
            $isOwner = true;
        }
        else
            $isOwner = false;

        view()->render('article_list', ['articles'=>$articles , 'pages' => $pages, 'next' => $next, 'isOwner' => $isOwner, 'userId' => $userId]);
    }
}