<?php
include '../system/db.php';

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;


return function (App $app) {
    $container = $app->getContainer();
    
    $db = new DB('localhost','root','root','projekt_4_rocnik');
    

    
    $app->get('/login[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
    	$data = array();
    	$data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');
    	
        
        return $container->get('renderer')->render($response, 'login.phtml', $data);
    });

    //////logout
    $app->get('/logout', function (Request $request, Response $response, array $args) use ($container, $db) {
 		       
    	$data = array();
    	$data['baseurl'] = $request->getUri()->getBasePath();
       
        unset($_SESSION['auth']);
        return $response->withRedirect($data['baseurl'].'/domov', 301);
    	
    });
    //////login post
    $app->post('/login[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
    	$data = array();
    	$data['baseurl'] = $request->getUri()->getBasePath();

    	$users = $db->select('uzivatelia','*','meno ="'.$db->escape_string($_POST['meno']).'" and heslo="'.md5($_POST['heslo']).'"');
    	//echo $db->getSQL();
    	
		if(isset($users[0]) && $users[0]['meno'] == $db->escape_string($_POST['meno']) && $users[0]['heslo'] == md5($_POST['heslo'] )){

            $_SESSION['auth'] = true;
    		$_SESSION['user'] = $users[0]['id'];
            $_SESSION['level'] = $users[0]['level'];

    		return $response->withRedirect($data['baseurl'].'/domov', 301);
		}
		else
		{
			return $response->withRedirect($data['baseurl'].'/login', 301);
		}

    });
    ///////USER
    $app->get('/user/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        
        // Render index view
        return $container->get('renderer')->render($response, 'users.phtml', $data);
    });
    /////Zmena/hesla
    $app->get('/user/heslo/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        
        // Render index view
        return $container->get('renderer')->render($response, 'heslo.phtml', $data);
    });
    $app->post('/user/heslo/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);

        }

        if(!($_POST['1_stare_heslo'] == $_POST['2_stare_heslo']))
        {
                
            $data['chyba'] = '*Heslá niesu rovnaké*';
            return $container->get('renderer')->render($response, 'heslo.phtml', $data);
        }
        else
        {
            $users = $db->select('uzivatelia','*','heslo="'.md5($_POST['1_stare_heslo']).'"');

            if(isset($users[0]) && $users[0]['heslo'] == md5($_POST['1_stare_heslo'] ))
            {
                if(empty($db->escape_string($_POST['nove_heslo'])))
                {
                    $data['chyba'] = '*Nové heslo nesmie byť prázdne*';
                    return $container->get('renderer')->render($response, 'heslo.phtml', $data);
                }

                $values['heslo'] = md5($_POST['nove_heslo']);
                $db->update('uzivatelia',$values,'id='.$_SESSION['user']);

                unset($_SESSION['auth']);
                return $response->withRedirect($data['baseurl'].'/domov', 301);
            }
            else
            {   
                $data['chyba'] = '*Stare heslo je nesprávne*';
                return $container->get('renderer')->render($response, 'heslo.phtml', $data);
            }
        }
        return $response->withRedirect($data['baseurl'].'/domov', 301);
    });
    /////user/save
    $app->any('/user/save/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        
        $docr = dirname($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF']).'/img/users/';
       
        if($_FILES["img"]["error"] == UPLOAD_ERR_OK)
        {
         if(move_uploaded_file($_FILES["img"]["tmp_name"], $docr.$_FILES["img"]["name"]))
         {
            $values['img'] = $_FILES["img"]["name"];
         }
        }

        $values['meno'] = $db->escape_string($_POST['meno']);
       
        if(!empty($_POST['meno']))
        {
            $db->update('uzivatelia',$values,'id='.(int)$args['id']);
            
        }
         // Render index view
        return $response->withRedirect($data['baseurl'].'/domov', 301);
    });
    ///admin
    $app->get('/admin[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }

        // Render index view
        return $container->get('renderer')->render($response, 'admin/index.phtml', $data);
    });
    //////ADMIN/NOVINKY///
    $app->get('/admin/novinky[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        $sql = "SELECT * FROM news ORDER BY date_c DESC";
        $data['novinky'] = $db->query($sql);
        // Render index view
        return $container->get('renderer')->render($response, 'admin/novinky.phtml', $data);
    });
    //////ADMIN/VYDANIA///
    $app->get('/admin/vydania[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        $sql = "SELECT * FROM new_release ORDER BY date_c DESC";
        $data['release'] = $db->query($sql);
        // Render index view
        return $container->get('renderer')->render($response, 'admin/vydania.phtml', $data);
    });
    ////ADMIN/VYDANIA/NEW////
    $app->get('/admin/vydania/new[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        
        // Render index view
        return $container->get('renderer')->render($response, 'admin/vydania-edit.phtml', $data);
    });
    /////ADMIN/NOVINKY/EDIT///
    $app->get('/admin/vydania/edit/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }

        $data['release'] = $db->select('new_release','*','id='.(int)$args['id']);
        
        // Render index view
        return $container->get('renderer')->render($response, 'admin/vydania-edit.phtml', $data);
    });
    /////ADMIN/VYDANIA/EDIT/SAVE///
    $app->any('/admin/vydania/save[/[{id:[0-9]+}]]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        
        $docr = dirname($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF']).'/img/new_r/';
       
        if($_FILES["img"]["error"] == UPLOAD_ERR_OK)
        {
         if(move_uploaded_file($_FILES["img"]["tmp_name"], $docr.$_FILES["img"]["name"]))
         {
            $values['img'] = $_FILES["img"]["name"];
         }
        }

        $values['header'] = $db->escape_string($_POST['header']);
        if(!empty($_POST['date_c']))
        {
            $values['date_c'] = $db->escape_string($_POST['date_c']);
        }
        
        

        if(!empty($_POST['header']))
        {
            if(empty($args['id']))
            {
                $db->insert('new_release',$values);
            }
            else
            {
                $db->update('new_release',$values,'id='.(int)$args['id']);
            }
        }
         // Render index view
        return $response->withRedirect($data['baseurl'].'/admin/vydania', 301);
    });
    //////ADMIN/VYDANIA/DELTE///
    $app->get('/admin/vydania/delete/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {

        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }

        $sql = "DELETE from new_release where id= '".$db->escape_string($args['id'])."'";
        $db->query($sql);
        return $response->withRedirect($data['baseurl'].'/admin/vydania', 301);

        });
    ////ADMIN/NOVINKY/NEW////
    $app->get('/admin/novinky/new[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        
        // Render index view
        return $container->get('renderer')->render($response, 'admin/novinky-edit.phtml', $data);
    });
    /////ADMIN/NOVINKY/EDIT///
    $app->get('/admin/novinky/edit/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }

        $data['novinky'] = $db->select('news','*','id='.(int)$args['id']);
        
        // Render index view
        return $container->get('renderer')->render($response, 'admin/novinky-edit.phtml', $data);
    });
    /////ADMIN/NOVINKY/EDIT/SAVE///
    $app->any('/admin/novinky/save[/[{id:[0-9]+}]]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        
        $docr = dirname($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF']).'/img/novinky/';
        //var_dump($_FILES);
        //exit();
        if($_FILES["img"]["error"] == UPLOAD_ERR_OK)
        {
         if(move_uploaded_file($_FILES["img"]["tmp_name"], $docr.$_FILES["img"]["name"]))
         {
            $values['img'] = $_FILES["img"]["name"];
         }
        }

        if($_FILES["main_img"]["error"] == UPLOAD_ERR_OK)
        {
         if(move_uploaded_file($_FILES["main_img"]["tmp_name"], $docr.$_FILES["main_img"]["name"]))
         {
            $values['main_img'] = $_FILES["main_img"]["name"];
         }
        }
        

        $values['header'] = $db->escape_string($_POST['header']);
        $values['main_content'] = $db->escape_string($_POST['content']);
        

        if(!empty($_POST['header']) && !empty($_POST['content']) )
        {
            if(empty($args['id']))
            {
                $db->insert('news',$values);
            }
            else
            {
                $db->update('news',$values,'id='.(int)$args['id']);
            }
        }
         // Render index view
        return $response->withRedirect($data['baseurl'].'/admin/novinky', 301);
    });
    //////ADMIN/NOVINKY/DELTE///
    $app->get('/admin/novinky/delete/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {

        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }

        $sql = "DELETE from news where id= '".$db->escape_string($args['id'])."'";
        $db->query($sql);
        return $response->withRedirect($data['baseurl'].'/admin/novinky', 301);

        });
    //////ADMIN/KOMENTARE/////
    $app->get('/admin/komentare/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        $sql = "SELECT * from reply where topic_fk= ".$db->escape_string($args['id'])." ORDER BY date_c DESC";
        //$sql = "SELECT from reply ORDER BY date_c DESC where topic_fk= '".$db->escape_string($args['id'])."' ";
        $data['topics'] = $db->query($sql);
        // Render index view
        return $container->get('renderer')->render($response, 'admin/komentare.phtml', $data);
    });
    ////ADMIN/KOMENTARE/SAVE////
    $app->any('/admin/komentare/save[/[{id:[0-9]+}]]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        
        
        $values['answer'] = $db->escape_string($_POST['popis']);
        
        if(!empty($_POST['popis']) )
        {
           
         $db->update('reply',$values,'id='.(int)$args['id']);
            
        }
         // Render index view
        return $response->withRedirect($data['baseurl'].'/admin/komentare/'.$_POST['topic_fk'], 301);
    });
    /////ADMIN/KOMENTARE/DELETE
    $app->get('/admin/komentare/delete/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {

        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }

        $data['reply'] = $db->select('reply','*','id='.(int)$args['id']);

        $sql = "DELETE from reply where id= '".$db->escape_string($args['id'])."'";
        $db->query($sql);
        
        return $response->withRedirect($data['baseurl'].'/admin/komentare/'.$data['reply'][0]['topic_fk'], 301);

        });
    /////ADMIN/FORUM/EDIT/////
    $app->get('/admin/komentare/edit/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }

        $data['topics'] = $db->select('reply','*','id='.(int)$args['id']);
        
        // Render index view
        return $container->get('renderer')->render($response, 'admin/komentare-edit.phtml', $data);
    });
    //////ADMIN/FORUM/////
    $app->get('/admin/forum[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        $data['topics'] = $db->select('topics');
        // Render index view
        return $container->get('renderer')->render($response, 'admin/forum.phtml', $data);
    });
    /////ADMIN/FORUM/EDIT/////
    $app->get('/admin/forum/edit/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }

        $data['topics'] = $db->select('topics','*','id='.(int)$args['id']);
        
        // Render index view
        return $container->get('renderer')->render($response, 'admin/forum-edit.phtml', $data);
    });
    ////ADMIN/FORUM/SAVE////
    $app->any('/admin/forum/save[/[{id:[0-9]+}]]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        
        $values['header'] = $db->escape_string($_POST['header']);
        $values['popis'] = $db->escape_string($_POST['popis']);
        
        if(!empty($_POST['header']) && !empty($_POST['popis']) )
        {
            if(empty($args['id']))
            {
                $db->insert('topics',$values);
            }
            else
            {
                $db->update('topics',$values,'id='.(int)$args['id']);
            }
        }
         // Render index view
        return $response->withRedirect($data['baseurl'].'/admin/forum', 301);
    });
    /////ADMIN/FORUM/DELETE
    $app->get('/admin/forum/delete/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {

        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }

        $sql = "DELETE from topics where id= '".$db->escape_string($args['id'])."'";
        $sql_n = "DELETE from reply where topic_fk= '".$db->escape_string($args['id'])."'";

        $db->query($sql);
        $db->query($sql_n);
        return $response->withRedirect($data['baseurl'].'/admin/forum', 301);

        });
    //////ADMIN/CAROUSEL/////
    $app->get('/admin/carousel[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        $data['carousel'] = $db->select('carousel');
        // Render index view
        return $container->get('renderer')->render($response, 'admin/carousel.phtml', $data);
    });
    /////ADMIN/CAROUSEL/EDIT/////
    $app->get('/admin/carousel/edit/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }

        $data['carousel'] = $db->select('carousel','*','id='.(int)$args['id']);
        
        // Render index view
        return $container->get('renderer')->render($response, 'admin/carousel-edit.phtml', $data);
    });
    /////ADMIN/CAROUSEL/EDIT/SAVE///
    $app->any('/admin/carousel/save[/[{id:[0-9]+}]]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        
        $docr = dirname($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF']).'/img/carousel/';

        if($_FILES["img"]["error"] == UPLOAD_ERR_OK)
        {
         if(move_uploaded_file($_FILES["img"]["tmp_name"], $docr.$_FILES["img"]["name"]))
         {
            $values['img_name'] = $_FILES["img"]["name"];
         }
        }

        $values['header'] = $db->escape_string($_POST['header']);
        $values['content'] = $db->escape_string($_POST['content']);
        $values['trailer'] = $db->escape_string($_POST['trailer']);
        $values['obchod'] = $db->escape_string($_POST['obchod']);
        if(!empty($_POST['header']) && !empty($_POST['content']) )
        {
            if(empty($args['id']))
            {
                $db->insert('carousel',$values);
            }
            else
            {
                $db->update('carousel',$values,'id='.(int)$args['id']);
            }
        }
         // Render index view
        return $response->withRedirect($data['baseurl'].'/admin/carousel', 301);
    });
    /////ADMIN/CAROUSEL/NEW///
    $app->get('/admin/carousel/new[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        
        // Render index view
        return $container->get('renderer')->render($response, 'admin/carousel-edit.phtml', $data);
    });
    /////ADMIN/CAROUSEL/DELETE
    $app->get('/admin/carousel/delete/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {

        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        $data['carousel'] = $db->select('carousel','*','id='.$db->escape_string($args['id']));

        if (file_exists($data['baseurl'].'/img/carousel/'.$data['carousel'][0]['img_name'])) 
        {
            unlink($data['baseurl'].'/img/carousel/'.$data['carousel'][0]['img_name']);
        }
            //var_dump(realpath($data['baseurl'].'/img/carousel/'.$data['carousel'][0]['img_name']));
            //exit();
            //unlink($data['baseurl'].'/img/carousel/'.$data['carousel'][0]['img_name']);

        $sql = "DELETE from carousel where id= '".$db->escape_string($args['id'])."'";
        $db->query($sql);
        return $response->withRedirect($data['baseurl'].'/admin/carousel', 301);

        });
    //////ADMIN/RECENZIE/////
    $app->get('/admin/recenzie[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        $sql = "SELECT * FROM hry ORDER BY date_c DESC";
        $data['recenzie'] = $db->query($sql);
        // Render index view
        return $container->get('renderer')->render($response, 'admin/hry.phtml', $data);
    });
    /////ADMIN/RECENZIE/EDIT/////
    $app->get('/admin/recenzie/edit/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }

        $data['recenzie'] = $db->select('hry','*','id='.(int)$args['id']);
        
        // Render index view
        return $container->get('renderer')->render($response, 'admin/hry-edit.phtml', $data);
    });
    /////ADMIN/RECENZIE/EDIT/SAVE///
    $app->any('/admin/recenzie/save[/[{id:[0-9]+}]]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        
        $docr = dirname($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF']).'/img/hry/';
        //var_dump($_FILES);
        //exit();
        if($_FILES["img"]["error"] == UPLOAD_ERR_OK)
        {
         if(move_uploaded_file($_FILES["img"]["tmp_name"], $docr.$_FILES["img"]["name"]))
         {
            $values['img_mala'] = $_FILES["img"]["name"];
         }
        }

        if($_FILES["main_img"]["error"] == UPLOAD_ERR_OK)
        {
         if(move_uploaded_file($_FILES["main_img"]["tmp_name"], $docr.$_FILES["main_img"]["name"]))
         {
            $values['img_velka'] = $_FILES["main_img"]["name"];
         }
        }
        

        $values['header'] = $db->escape_string($_POST['header']);
        $values['main_content'] = $db->escape_string($_POST['content']);
        $values['obchod'] = $db->escape_string($_POST['obchod']);
        $values['trailer'] = $db->escape_string($_POST['trailer']);
        $values['hodnotenie'] = $db->escape_string($_POST['hodnotenie']);
        

        if(!empty($_POST['header']) && !empty($_POST['content']) )
        {
            if(empty($args['id']))
            {
                $db->insert('hry',$values);
            }
            else
            {
                $db->update('hry',$values,'id='.(int)$args['id']);
            }
        }
         // Render index view
        return $response->withRedirect($data['baseurl'].'/admin/recenzie', 301);
    });
    /////ADMIN/RECENZIE/NEW///
    $app->get('/admin/recenzie/new[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('second_menu');

        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        else{
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        
        // Render index view
        return $container->get('renderer')->render($response, 'admin/hry-edit.phtml', $data);
    });
    /////ADMIN/RECENZIE/DELETE
    $app->get('/admin/recenzie/delete/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {

        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        if(!(isset($_SESSION['auth']) && $_SESSION['auth'] == true && $_SESSION['level'] == 'admin'))
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }

        $sql = "DELETE from hry where id= '".$db->escape_string($args['id'])."'";
        $db->query($sql);
        return $response->withRedirect($data['baseurl'].'/admin/recenzie', 301);

        });







    //////registracia////
    $app->get('/registracia[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');

        if(isset($_SESSION['auth']) && $_SESSION['auth'] == true)
        {
            return $response->withRedirect($data['baseurl'].'/domov', 301);
        }
        // Render index view
        return $container->get('renderer')->render($response, '/registracia.phtml', $data);
    });
    ////////registracia-post
    $app->post('/registracia[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');

        if(empty(trim($_POST['meno'])) || empty(trim($_POST['heslo'])) )
        {
                $data['chyba_e'] = 'Prázdne meno alebo heslo';
 
            return $container->get('renderer')->render($response, 'registracia.phtml', $data);           
        }

    $users = $db->select('uzivatelia','*','meno ="'.$db->escape_string($_POST['meno']).'"');

        
        if(isset($users[0]) && $users[0]['meno'] == $db->escape_string($_POST['meno']) ){

            $data['chyba'] = 'Toto meno už niekto používa';

            return $container->get('renderer')->render($response, 'registracia.phtml', $data);
        }
        else{   
            $docr = dirname($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF']).'/img/users/';

            if($_FILES["img"]["error"] == UPLOAD_ERR_OK)
            {
             if(move_uploaded_file($_FILES["img"]["tmp_name"], $docr.$_FILES["img"]["name"]))
             {
                $values['img'] = $_FILES["img"]["name"];
             }
            }

                $values['meno'] = $db->escape_string($_POST['meno']);
                $values['level'] = 'user';
                $values['heslo'] = md5($_POST['heslo']);
                $db->insert('uzivatelia',$values);

                return $response->withRedirect($data['baseurl'].'/login', 301);
        }
 
    });
    ////Uvod////////////////////////////////////////////////////////////////////////////
    $app->get('/[domov]', function (Request $request, Response $response, array $args) use ($container, $db) {
        $data = array();
    	$data['baseurl'] = $request->getUri()->getBasePath();
    	$data['rows'] = $db->select('hl_menu');

        if((isset($_SESSION['auth']) && $_SESSION['auth'] == true))
        {
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }


        $sql = 'SELECT * FROM carousel ORDER BY RAND() LIMIT 1';
        $data['carousel'] = $db->query($sql);

        $sql_n = 'SELECT * FROM news ORDER BY date_c DESC LIMIT 4';
        $data['news'] = $db->query($sql_n);

        $sql_forum = 'SELECT * from topics ORDER BY date_c DESC LIMIT 3';
        $data['forum'] = $db->query($sql_forum);

        $data['new_r'] = $db->select('new_release');

        return $container->get('renderer')->render($response, 'index.phtml', $data);
    });
    ///NOVINKY//////////////////////////////////////////////////////////////////////////////////////////////////
    $app->any('/novinky[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        $data = array();

        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');

        if((isset($_SESSION['auth']) && $_SESSION['auth'] == true))
        {
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }

        $page = '0';

            if(isset($_GET['page']) && !empty($_GET['page']))
            {

                $page = 3 * ((int)$_GET['page']-1);

            }

        $sql_paging = "SELECT * FROM news ORDER BY date_c DESC LIMIT ".$page." , 3";
        $condition = '';
        
        $data['new_r'] = $db->query($sql_paging);
        $data['cnt'] = ceil($db->count('news', $condition)/3);


        return $container->get('renderer')->render($response, 'novinky.phtml', $data);
    });

    /////NOVINKA/////////
    $app->get('/novinky/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        $data = array();
        if((isset($_SESSION['auth']) && $_SESSION['auth'] == true))
        {
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }

        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');

        $data['new_r'] = $db->select('news','*','id='.(int)$args['id']);
        //$sql = "SELECT * from news where id= '".$db->escape_string($args['id'])."'";
        //$data['new_r'] = $db->query($sql);

        return $container->get('renderer')->render($response, 'j_novinka.phtml', $data);
    });
    //////paging///nefunkčne
    $app->get('/admin/articles[/]', function (Request $request, Response $response, array $args) use ($container, $db) 
        {   
            $data = array();
                if((isset($_SESSION['auth']) && $_SESSION['auth'] == true))
                {
                    $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
                }

                $data['baseurl'] = $request->getUri()->getBasePath();
                $data['rows'] = $db->select('hl_menu');

           
            $page = '0';

            if(isset($_GET['page']) && !empty($_GET['page']))
            {

                $page = 5 * ((int)$_GET['page']-1);

            }

            $condition = '';

            if(isset($_GET['search']) && !empty($_GET['search']))
            {

                $condition = 'header LIKE "%'.$_GET['search'].'%" or content LIKE "%'.$_GET['search'].'%"';

            }

            $data['baseurl'] = $request->getUri()->getBasePath();            $data['themes'] = $db->select('themes');
            $data['rows'] = $db->select('news', '*', $condition, $page.' , 2');
            $data['cnt'] = ceil($db->count('news', $condition)/2);

            return $container->get('renderer')->render($response, 'admin/articles.tpl', $data);
            
        });
    /////////////recenzie//////
    $app->get('/recenzie[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        $data = array();
        if((isset($_SESSION['auth']) && $_SESSION['auth'] == true))
        {
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }

        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');

        $page = '0';

            if(isset($_GET['page']) && !empty($_GET['page']))
            {
                $page = 4 * ((int)$_GET['page']-1);
            }

        $condition = '';

            if(isset($_GET['keyw']) && !empty($_GET['keyw']))
            {
                $condition = 'header LIKE "%'.$_GET['keyw'].'%" or hodnotenie LIKE "%'.$_GET['keyw'].'%"';
            }

        $sql_paging = 'SELECT * FROM hry '.(empty($condition) ? '' : ' WHERE '.$condition).' ORDER BY date_c DESC LIMIT '.$page.' , 4';
        
        
            $data['game'] = $db->query($sql_paging);
            $data['cnt'] = ceil($db->count('news', $condition)/4);


        return $container->get('renderer')->render($response, 'recenzie.phtml', $data);
    });
   //////recenzia//////
   $app->get('/recenzie/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        $data = array();
        if((isset($_SESSION['auth']) && $_SESSION['auth'] == true))
        {
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');

        $data['game'] = $db->select('hry','*','id='.(int)$args['id']);
        //$sql = 'SELECT * FROM hry ORDER BY date_c DESC';
        //$data['game'] = $db->query($sql);

        return $container->get('renderer')->render($response, 'recenzie-hra.phtml', $data);
    });
   ////////FORUM////
   $app->get('/forum[/]', function (Request $request, Response $response, array $args) use ($container, $db) {
        $data = array();
        if((isset($_SESSION['auth']) && $_SESSION['auth'] == true))
        {
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');
        $sql = 'SELECT * from topics ORDER BY date_c DESC';
        $data['topic'] = $db->query($sql);


        return $container->get('renderer')->render($response, 'forum.phtml', $data);
    });
   ///////JEDEN_TOPIC/////
   $app->get('/forum/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        $data = array();
        if((isset($_SESSION['auth']) && $_SESSION['auth'] == true))
        {
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');

        $sql_topic = "SELECT topics.id, topics.header, topics.popis, topics.date_c, uzivatelia.meno, uzivatelia.img FROM topics INNER JOIN uzivatelia ON topics.user_fk = uzivatelia.id WHERE topics.id=".$db->escape_string($args['id']);

        $data['topic'] = $db->query($sql_topic);

        $sql = "SELECT reply.id, reply.topic_fk, reply.answer, reply.date_c, uzivatelia.meno, uzivatelia.img FROM reply INNER JOIN uzivatelia ON reply.user_fk = uzivatelia.id WHERE reply.topic_fk=".$db->escape_string($args['id'])." ORDER BY date_c DESC";

        //$sql = "SELECT * from reply where topic_fk= ".$db->escape_string($args['id'])." ORDER BY date_c DESC";

        //$data['reply'] = $db->select('reply','*','topic_fk='.(int)$args['id']);
        $data['reply'] = $db->query($sql);
        


        return $container->get('renderer')->render($response, 'reply.phtml', $data);
    });
   ////////odpoved_na_otazku_fora->databaza////
   $app->any('/forum/new/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container, $db) {
        $data = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        
        if(!empty(trim($_POST['odpoved_na_topic'])))
            {
                $values['answer'] = $db->escape_string($_POST['odpoved_na_topic']);
                $values['topic_fk'] = (int)$args['id'];
                 $values['user_fk'] = $_SESSION['user'];

                $db->insert('reply',$values);
            }
        return $response->withRedirect($data['baseurl'].'/forum/'.$args['id'], 301);
    });
   ///////otvorenie_formulara_na_pridanie_otazky////
   $app->get('/topic/new', function (Request $request, Response $response, array $args) use ($container, $db) {
        $data = array();
        if((isset($_SESSION['auth']) && $_SESSION['auth'] == true))
        {
            $data['p_user'] = $db->select('uzivatelia','*','id='.$_SESSION['user']);
        }
        $data['baseurl'] = $request->getUri()->getBasePath();
        $data['rows'] = $db->select('hl_menu');
        
        
         return $container->get('renderer')->render($response, 'new_forum.phtml', $data);
    });
   //////novy_topic_save/////
   $app->any('/forum/save', function (Request $request, Response $response, array $args) use ($container, $db) {

        $data = array();
        $values = array();
        $data['baseurl'] = $request->getUri()->getBasePath();
        
        if(!empty(trim($_POST['header'])) && !empty(trim($_POST['popis'])))
        {
            $values['header'] = $db->escape_string($_POST['header']);
            $values['popis'] = $db->escape_string($_POST['popis']);
            $values['user_fk'] = $_SESSION['user'];

            $db->insert('topics',$values);
        }
        return $response->withRedirect($data['baseurl'].'/forum', 301);
    });
};
