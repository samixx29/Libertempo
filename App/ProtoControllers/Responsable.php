<?php
namespace App\ProtoControllers;

/**
 * ProtoContrôleur d'utilisateur, en attendant la migration vers le MVC REST
 *
 * @since  1.9
 * @author wouldsmina <wouldsmina@tuxfamily.org>
 * @author Prytoegrian <prytoegrian@protonmail.com>
 */
class Responsable
{
    /**
     * Retourne l'id des groupes d'un responsable
     *
     * @param string $resp
     * 
     * @return array $ids
     */
    public static function getIdGroupeResp($resp)
    {
        $ids = [];

        $sql = \includes\SQL::singleton();
        $req = 'SELECT gr_gid AS id FROM `conges_groupe_resp` WHERE gr_login =\''.$resp.'\'';
        $res = $sql->query($req);

        while ($data = $res->fetch_array()) {
            $ids[] = (int) $data['id'];
        }

        return $ids;
    }

    /**
     * Retourne l'id des groupes d'un grand responsable
     *
     * @param string $gresp
     * 
     * @return array $ids
     */
    public static function getIdGroupeGrandResponsable($gresp)
    {
        $ids=[];
         $sql = \includes\SQL::singleton();
         $req = 'SELECT ggr_gid AS id FROM `conges_groupe_grd_resp` WHERE ggr_login =\''.$gresp.'\'';
         $res = $sql->query($req);

         while ($data = $res->fetch_array()) {
             $ids[] = (int) $data['id'];
         }

         return $ids;
    }

    /**
     * Retourne le login des membres d'une liste de groupes
     *
     * @param array $groupesId
     * 
     * @return array $users
     */
    public static function getUsersGroupe(array $groupesId)
    {
        if (empty($groupesId)) {
            return [];
        }
        
        $users = [];
        
         $sql = \includes\SQL::singleton();
         $req = 'SELECT gu_login FROM `conges_groupe_users` WHERE gu_gid IN (' . implode(',', $groupesId) . ')';
         $res = $sql->query($req);

         while ($data = $res->fetch_array()) {
             $users[] = $data['gu_login'];
         }

         return $users;
    }

    
    /**
     * Retourne le login des utilisateurs d'un responsable direct
     *
     * @param string $resp
     * 
     * @return array $users
     */
    public static function getUsersRespDirect($resp)
    {
        
        $users = [];
        
         $sql = \includes\SQL::singleton();
         $req = 'SELECT u_login FROM `conges_users` WHERE u_resp_login ="'. $resp . '"';
         $res = $sql->query($req);

         while ($data = $res->fetch_array()) {
             $users[] = $data['u_login'];
        }
        return $users;
    }
    
    public static function getLoginGrandResponsableUtilisateur($user) {
        $groupesIdUser = \App\ProtoControllers\Utilisateur::getGroupesId($user);
        
        $grandResp = [];
        $sql = \includes\SQL::singleton();
        $req = 'select ggr_login FROM conges_groupe_grd_resp where ggr_gid  IN (\'' . implode(',', $groupesIdUser) . '\')';
        $res = $sql->query($req);
        
        while ($data = $res->fetch_array()) {
             $grandResp[] = $data['ggr_login'];
        }
        return $grandResp;
    }
    
    public static function getResponsablesUtilisateur($user) {
        
        $responsables = \App\ProtoControllers\Responsable::getResponsableGroupe(\App\ProtoControllers\Utilisateur::getGroupesId($user));
        $responsables[] = \App\ProtoControllers\Responsable::getResponsableDirect($user);
        $responsables = array_unique($responsables);
        
        return $responsables;
    }

    public static function getResponsableDirect($user) {
        $resp = [];
        $sql = \includes\SQL::singleton();
        $req = 'SELECT u_resp_login FROM conges_users WHERE u_login ="' . \includes\SQL::quote($user) . '"';
        $res = $sql->query($req);
        return $res->fetch_array()['u_resp_login'];
        
    }
    
    private static function getResponsableGroupe(array $groupesId) {
        
        $responsable = [];
        
        $sql = \includes\SQL::singleton();
        $req = 'SELECT gr_login FROM conges_groupe_resp WHERE gr_gid IN (\'' . implode(',', $groupesId) . '\')';
        $res = $sql->query($req);

         while ($data = $res->fetch_array()) {
             $responsable[] = $data['gr_login'];
         }
         
         return $responsable;
    }

    /**
     * Vérifie si un utilisateur est bien le responsable d'un employé
     * 
     * @param string $resp
     * @param string $user
     * 
     * @return bool
     */
    public static function isRespDeUtilisateur($resp, $user) {
        return \App\ProtoControllers\Responsable::isRespDirect($resp, $user) || \App\ProtoControllers\Responsable::isRespGroupe($resp, \App\ProtoControllers\Utilisateur::getGroupesId($user));
    }
    
    /**
     * Vérifie si un utilisateur est bien le grand responsable d'un employé
     * 
     * @param string $resp
     * @param array $groupesId
     * 
     * @return bool
     */
    public static function isGrandRespDeGroupe($resp, array $groupesId) {
        $sql = \includes\SQL::singleton();
        $req = 'SELECT EXISTS (
                    SELECT ggr_gid
                    FROM conges_groupe_grd_resp
                    WHERE ggr_gid IN (\'' . implode(',', $groupesId) . '\')
                        AND ggr_login = "'.\includes\SQL::quote($resp).'"
                )';
        $query = $sql->query($req);

        return 0 < (int) $query->fetch_array()[0];
    }

    /**
     * Verifie si un utilisateur est responsable d'une liste de groupe
     * 
     * @param string $resp
     * @param array $groupesId
     * 
     * @return bool
     */
    public static function isRespGroupe($resp, array $groupesId)
    {
        $sql = \includes\SQL::singleton();
        $req = 'SELECT EXISTS (
                    SELECT gr_gid
                    FROM conges_groupe_resp
                    WHERE gr_gid IN (\'' . implode(',', $groupesId) . '\')
                        AND gr_login = "'.\includes\SQL::quote($resp).'"
                )';
        $query = $sql->query($req);

        return 0 < (int) $query->fetch_array()[0];
    }

    /**
     *  Verifie si un utilisateur est responsable d'un employé
     * 
     * @param string $resp
     * @param string $user
     * 
     * @return bool
     */
    public static function isRespDirect($resp, $user)
    {
        $sql = \includes\SQL::singleton();
        $req = 'SELECT EXISTS (
                    SELECT u_resp_login 
                    FROM conges_users 
                    WHERE u_login ="'.\includes\SQL::quote($user).'"
                        AND u_resp_login ="'.\includes\SQL::quote($resp).'"
           )';
    $query = $sql->query($req);

    return 0 < (int) $query->fetch_array()[0];
    }

    /**
     * Vérifie si le groupe d'un employé est en double validation
     * 
     * @param string $user
     * 
     * @return bool
     */
    public static function isDoubleValGroupe($user)
    {
        $groupes = [];
        $groupes = \App\ProtoControllers\Utilisateur::getGroupesId($user);
        if(empty($groupes)){
            return false;
        }
        
        $sql = \includes\SQL::singleton();
        $req = 'SELECT EXISTS (
                    SELECT g_double_valid
                    FROM conges_groupe
                    WHERE g_gid ='. $groupes[0] . '
                    AND g_double_valid = "Y"
                )';
        $query = $sql->query($req);

        return 0 < (int) $query->fetch_array()[0];
    }

}