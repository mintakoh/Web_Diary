<?php

namespace Controller;

use Model\Article;
use Model\Receipt;
use Model\User;
use Repository\ArticleFileRepository;

class ArticleController
{
    public static function index(){

        /** @var User $user */
        $user = getCurrentUser();

        if($user == null) {
            header('Location: /?r=/auth');
        }
        $articles = \IoC::resolve('diaryStore')->getArticlesByUserId($user->getId());
        view()->render('article_list', ['articles'=>$articles]);
    }

    public static function write() {

        /** @var User $user */
        $user = getCurrentUser();

        if($user == null) {
            header('Location: /?r=/auth');
        }

        view()->render('article_create');
    }

    public static function create(){

        $article = new Article(getCurrentUser(),$_POST["date"],$_POST["subject"],$_POST["content"],$_POST["secret"]);

        for($i = 0; $i < count($_POST["price"]); $i++) {
            $article->addReceipt(new Receipt($_POST["summary"][$i],$_POST["price"][$i],$_POST["currency"][$i]));
        }

        \IoC::resolve('diaryStore')->createArticle($article);

        header('Location: /?r=/article/'.$article->getId());
    }

    public static function update($id){
        $article = \IoC::resolve('diaryStore')->getArticleById($id);

        $article->setDate($_POST["date"]);
        $article->setSubject($_POST["subject"]);
        $article->setContent($_POST["content"]);

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


        \IoC::resolve('diaryStore')->modifyArticle($article);

        header('Location: /?r=/article/'.$article->getId());
    }

    public static function modify($id){
        $article = \IoC::resolve('diaryStore')->getArticleById($id);

        if(getCurrentUser()->getId() !== $article->getUser()->getId())
        {
            view()->render('not_authorized', ['article'=>$article]);
            return;
        }
        view()->render('article_modify', ['article'=>$article]);
    }

    public static function view($id){

        /** @var User $currentUser */
        $currentUser = getCurrentUser();

        $article = \IoC::resolve('diaryStore')->getArticleById($id);
        $isOwner = false;
        if(isset($currentUser) && $article->getUser()->getId() == $currentUser->getId()){
            $isOwner = true;
        }
        view()->render('article', ['article'=>$article, 'isOwner'=>$isOwner]);
    }

    public static function remove($id){
        \IoC::resolve('diaryStore')->removeArticleById($id);

        header('Location: /?r=/article');
    }

    public static function userArticles($userId) {
        $articles = \IoC::resolve('diaryStore')->getArticlesByUserId($userId);
        view()->render('article_list', ['articles'=>$articles]);
    }
}