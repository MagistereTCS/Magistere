<?php
require_once('../../../config.php');
require_once('../lib.php');

require_login();
global $DB, $USER;

$ctid = required_param("id", PARAM_INT);
$sfid = required_param("sfid", PARAM_INT);
$popularity = optional_param("popularity", 0, PARAM_INT);
$liked = optional_param("liked", 0, PARAM_INT);
$favorite = optional_param("favorite", 0, PARAM_INT);
$favorited = optional_param("favorited", 0, PARAM_INT);
$sort = optional_param("sort", 0, PARAM_INT);
$filter = optional_param("filter", "", PARAM_TEXT);
$sortup = optional_param("sortup", "", PARAM_TEXT);

$sf = new SocialForum($sfid);

// Lorsque l'on clique sur un bouton Like
if($popularity){
    if(!$liked){
        if(!$sf->get_popularity_by_user_and_contribution($ctid, $USER->id)) {
            $sf->add_popularity($ctid, $USER->id);
            $liked = 1;
        }
    } else {
        $sf->delete_popularity($ctid, $USER->id);
        $liked = 0;
    }
    $sf = new SocialForum($sfid);
    $data_array = array(
        'count' => count($sf->get_popularities_by_contribution_id($ctid)),
        'liked' => $liked,
        'ctid' => $ctid
    );
}

// Lorsque l'on clique sur un bouton Favoris
if($favorite){
    if(!$favorited){
        if(!$sf->get_favorite_by_user_and_contribution($ctid, $USER->id)) {
            $sf->add_favorite($ctid, $USER->id);
            $favorited = 1;
        }
    } else {
        $sf->delete_favorite($ctid, $USER->id);
        $favorited = 0;
    }
    $data_array = array(
        'favorited' => $favorited,
        'ctid' => $ctid
    );
}

// Utilisation du filtre de tri
if($sort && $ctid){
    if($sortup == "true"){
        $sorting = 'ASC';
    } else {
        $sorting = 'DESC';
    }
    if($filter == "popularity"){ // Cas où l'option Nombre de votes est choisi
        $datas = $sf->sort_all_contributions_by_popularity($ctid, $sorting);
    } elseif($filter == "favorite"){ // Cas où l'option Favoris en premier est choisi
        $datas = $sf->sort_all_contributions_by_favorite($ctid, $sorting);
    } else { // Cas par défaut
        $datas = $sf->sort_all_contributions_by_date($ctid, $sorting);
    }

    $ids = array();
    foreach($datas as $data){
        array_push($ids, $data->id);
    }

    $data_array = array(
        'sort' => 1,
        'subid' => $ctid,
        'ids' => $ids
    );
}

// Envoi des données selon le cas
$data = json_encode($data_array);
echo $data;