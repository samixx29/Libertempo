<?php
/*************************************************************************************************
Libertempo : Gestion Interactive des Congés
Copyright (C) 2015 (Wouldsmina)
Copyright (C) 2015 (Prytoegrian)
Copyright (C) 2005 (cedric chauvineau)

Ce programme est libre, vous pouvez le redistribuer et/ou le modifier selon les
termes de la Licence Publique Générale GNU publiée par la Free Software Foundation.
Ce programme est distribué car potentiellement utile, mais SANS AUCUNE GARANTIE,
ni explicite ni implicite, y compris les garanties de commercialisation ou d'adaptation
dans un but spécifique. Reportez-vous à la Licence Publique Générale GNU pour plus de détails.
Vous devez avoir reçu une copie de la Licence Publique Générale GNU en même temps
que ce programme ; si ce n'est pas le cas, écrivez à la Free Software Foundation,
Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, États-Unis.
*************************************************************************************************
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or any later version.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*************************************************************************************************/
namespace App\ProtoControllers\Employe;

/**
 * ProtoContrôleur d'un congé, en attendant la migration vers le MVC REST
 *
 * @since  1.9
 * @author Prytoegrian <prytoegrian@protonmail.com>
 * @author Wouldsmina
 */
class Conge
{
    /**
     * Liste des congés
     *
     * @return string
     */
    public function getListe()
    {
        $return = '';
        $errorsLst=[];
        if($_SESSION['config']['where_to_find_user_email']=="ldap"){
            include_once CONFIG_PATH .'config_ldap.php';
        }

        if(!empty($_POST) && !$this->isSearch($_POST)) {
            if (0 < (int) \utilisateur\Fonctions::postDemandeCongesHeure($_POST, $errorsLst)) {
                $return .= '<div class="alert alert-info">'._('suppr_succes').'</div>';
            }
        }
        // on initialise le tableau global des jours fériés s'il ne l'est pas déjà :
        init_tab_jours_feries();

        $return .= '<h1>' . _('user_liste_conge_titre') . '</h1>';

        if (!empty($_POST) && $this->isSearch($_POST)) {
            $champsRecherche = $_POST['search'];
            $champsSql       = $this->transformChampsRecherche($_POST);
        } else {
            $champsRecherche = [
                'type' => 'cp',
            ];
            $champsSql       = [];
        }
        $params = $champsSql + [
            'p_login' => $_SESSION['userlogin'],
            'type'    => 'cp',
            'p_etat'  => 'demande',
        ]; // champs par défaut écrasés par postés

        $return.= $this->getFormulaireRecherche($champsRecherche);

        $table = new \App\Libraries\Structure\Table();
        $table->addClasses([
            'table',
            'table-hover',
            'table-responsive',
            'table-condensed',
            'table-striped',
        ]);
        $childTable = '<thead><tr><th>' . _('divers_debut_maj_1') . '</th><th>'. _('divers_fin_maj_1') .'</th><th>'. _('divers_type_maj_1') .'</th><th>'. _('divers_nb_jours_pris_maj_1') .'</th><th>Statut</th><th></th><th></th>';
        $childTable .= '</tr>';
        $childTable .= '</thead><tbody>';
        $listId = $this->getListeId($params);
        $session = session_id();
        if (empty($listId)) {
            $colonnes = 8;
            $childTable .= '<tr><td colspan="' . $colonnes . '"><center>' . _('aucun_resultat') .'</center></td></tr>';
        } else {
            $i = true;
            $listeConges = $this->getListeSQL($listId);
            $interdictionModification = $_SESSION['config']['interdit_modif_demande'];
            $affichageDateTraitement = $_SESSION['config']['affiche_date_traitement'];
            foreach ($listeConges as $conges) {
                /** Dates demande / traitement */
                $dateDemande = '';
                $dateReponse = '';
                if ($affichageDateTraitement) {
                    if (!empty($conges["p_date_demande"])) {
                        list($date, $heure) = explode(' ', $conges["p_date_demande"]);
                        $dateDemande = '(' . \App\Helpers\Formatter::dateIso2Fr($date) . ' ' . $heure . ') ';
                    }
                    if(null != $conges["p_date_traitement"]) {
                        list($date, $heure) = explode(' ', $conges["p_date_traitement"]);
                        $dateReponse = '(' . \App\Helpers\Formatter::dateIso2Fr($date) . ' ' . $heure . ') ';
                    }
                }

                /** Messages complémentaires */
                if (!empty($conges["p_commentaire"])) {
                    $messageDemande = '> Demande ' . $dateDemande . ":\n" . schars($conges["p_commentaire"]);
                } else {
                    $messageDemande = '';
                }
                if (!empty($conges["p_motif_refus"])) {
                    $messageReponse = '> Réponse ' . $dateReponse . ":\n" . schars($conges["p_motif_refus"]);
                } else {
                    $messageReponse = '';
                }

                $demi_j_deb = ($conges["p_demi_jour_deb"]=="am") ? 'matin' : 'après-midi';

                $demi_j_fin = ($conges["p_demi_jour_fin"] =="am") ? 'matin' : 'après-midi';
                $user_modif_demande="&nbsp;";

                // si on peut modifier une demande :on defini le lien à afficher
                if( !$interdictionModification && $conges["p_etat"] != "valid") {
                    //on ne peut pas modifier une demande qui a déja été validé une fois (si on utilise la double validation)
                    $user_modif_demande = '<a href="user_index.php?session=' . $session . '&p_num=' . $conges['p_num'] . '&onglet=modif_demande">' . _('form_modif') . '</a>' ;
                }
                $user_suppr_demande = '<a href="user_index.php?session=' . $session . '&p_num=' . $conges['p_num'] . '&onglet=suppr_demande">' . _('form_supprim') . '</a>';
                $childTable .= '<tr class="'.($i?'i':'p').'">';
                $childTable .= '<td class="histo">' . \App\Helpers\Formatter::dateIso2Fr($conges["p_date_deb"]) . ' <span class="demi">' . schars($demi_j_deb) . '</span></td>';
                $childTable .= '<td class="histo">' . \App\Helpers\Formatter::dateIso2Fr($conges["p_date_fin"]) . ' <span class="demi">' . schars($demi_j_fin) . '</span></td>' ;
                $childTable .= '<td class="histo">'.schars($conges["ta_libelle"]).'</td>' ;
                $childTable .= '<td class="histo">'.affiche_decimal($conges["p_nb_jours"]).'</td>' ;
                $childTable .= '<td>' . \App\Models\Conge::statusText($conges["p_etat"]) . '</td>';
                $childTable .= '<td class="histo">';
                if (!empty($messageDemande) || !empty($messageReponse)) {
                    $childTable .= '<i class="fa fa-comments" aria-hidden="true" title="' . $messageDemande . "\n\n" . $messageReponse . '"></i>';
                }
                $childTable .= '</td>' ;
                $childTable .= '<td class="histo">';
                if(!$interdictionModification) {
                    $childTable .= $user_modif_demande . '&nbsp;&nbsp;';
                }
                $childTable .= ($user_suppr_demande) . '</td>'."\n" ;
                $childTable .= '</tr>';
                $i = !$i;
            }
        }
        $childTable .= '</tbody>';
        $table->addChild($childTable);
        ob_start();
        $table->render();
        $return .= ob_get_clean();

        return $return;
    }

    /**
     * Y-a-t-il une recherche dans l'avion ?
     *
     * @param array $post
     *
     * @return bool
     */
    protected function isSearch(array $post)
    {
        return !empty($post['search']);
    }

    /**
     * Retourne le formulaire de recherche de la liste
     *
     * @param array $champs Champs de recherche (postés ou défaut)
     *
     * @return string
     */
    protected function getFormulaireRecherche(array $champs)
    {
        $session = session_id();
        $form = '';
        $form = '<form method="post" action="" class="form-inline search" role="form">';
        $form .= '<div class="form-group"><label class="control-label col-md-4" for="statut">Statut&nbsp;:</label><div class="col-md-8"><select class="form-control" name="search[p_etat]" id="statut">';
        foreach (\App\Models\Conge::getOptionsStatuts() as $key => $value) {
            $selected = (isset($champs['p_etat']) && $key == $champs['p_etat'])
                ? 'selected="selected"'
                : '';
            $form .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
        }
        $form .= '</select></div></div>';
        $form .= '<div class="form-group "><label class="control-label col-md-4" for="type">Type&nbsp;:</label><div class="col-md-8"><select class="form-control" name="search[type]" id="type">';
        foreach (\utilisateur\Fonctions::getOptionsTypeConges() as $key => $value) {
            $selected = (isset($champs['type']) && $key == $champs['type'])
                ? 'selected="selected"'
                : '';
            $form .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
        }
        $form .= '</select></div></div>';
        $form .= '<div class="form-group"><label class="control-label col-md-4" for="annee">Année&nbsp;:</label><div class="col-md-8"><select class="form-control" name="search[annee]" id="sel1">';
        foreach (\utilisateur\Fonctions::getOptionsAnnees() as $key => $value) {
            $selected = (isset($champs['annee']) && $key == $champs['annee'])
                ? 'selected="selected"'
                : '';
            $form .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
        }
        $form .= '</select></div></div><div class="form-group"><div class="input-group"><button type="submit" class="btn btn-default"><i class="fa fa-search" aria-hidden="true"></i></button>&nbsp;<a href="' . ROOT_PATH . 'utilisateur/user_index.php?session='. $session . '&onglet=liste_conge" type="reset" class="btn btn-default">Reset</a></div></div></form>';

        return $form;
    }

    /**
     * Transforme les champs de recherche afin d'être compris par la bdd
     *
     * @param array $post
     *
     * @return array
     */
    protected function transformChampsRecherche(array $post)
    {
        $champs = [];
        $search = $post['search'];
        foreach ($search as $key => $value) {
            if ('annee' === $key) {
                $champs['dateDebut'] = ((int) $value) . '-01-01';
                $champs['dateFin']   = ((int) $value) . '-12-31';
            } else {
                $champs[$key] = $value;
            }
        }

        return $champs;
    }

    /*
     * SQL
     */

     /**
      * Retourne une liste d'id de congés
      *
      * @param array $params Paramètres de recherche
      *
      * @return array
      */
    protected function getListeId(array $params)
    {
        $sql = \includes\SQL::singleton();
        if (!empty($params)) {
            $where = [];
            foreach ($params as $key => $value) {
                $value = $sql->quote($value);
                switch ($key) {
                    case 'dateDebut':
                        $where[] = 'p_date_deb >= "' . $value . '"';
                        break;
                    case 'dateFin':
                        $where[] = 'p_date_deb <= "' . $value . '"';
                        break;
                    case 'type':
                        $where[] = 'CTA.ta_short_libelle = "' . $value . '"';
                        break;
                    default:
                        $where[] = $key . ' = "' . $value . '"';
                        break;
                }
            }
        }
        $ids = [];
        $req = 'SELECT p_num AS id
                FROM conges_periode CP
                    INNER JOIN conges_type_absence CTA ON (CP.p_type = CTA.ta_id) '
                . ((!empty($where)) ? ' WHERE ' . implode(' AND ', $where) : '');
        $res = $sql->query($req);
        while ($data = $res->fetch_array()) {
            $ids[] = (int) $data['id'];
        }

        return $ids;
    }

    /**
     * Retourne une liste de congés
     *
     * @param array $listId
     *
     * @return array
     */
    protected function getListeSQL(array $listId)
    {
        if (empty($listId)) {
            return [];
        }
        $sql = \includes\SQL::singleton();
        $req = 'SELECT CP.*, CTA.ta_libelle
                FROM conges_periode CP
                    INNER JOIN conges_type_absence CTA ON (CP.p_type = CTA.ta_id)
                WHERE p_num IN (' . implode(',', $listId) . ')
                ORDER BY p_date_deb DESC';

        return $sql->query($req)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Retourne les demandes d'un employé
     * 
     */
    public static function getIdDemandesUtilisateur($user) {
        
        $ids = [];
        $sql = \includes\SQL::singleton();
        $req = 'SELECT p_num AS id
                FROM conges_periode
                WHERE p_login = \'' . $sql->quote($user) . '\'
                AND p_etat = \''. \App\Models\Conge::STATUT_DEMANDE.'\'';
        $res = $sql->query($req);
        while ($data = $res->fetch_array()) {
            $ids[] = (int) $data['id'];
        }
        
        return $ids;
    }


    /**
     * Vérifie l'existence de congé basée sur les critères fournis
     *
     * @param array $params
     *
     * @return bool
     * @TODO: à terme, à baser sur le getList()
     */
    public function exists(array $params)
    {
        $sql = \includes\SQL::singleton();

        $where = [];
        foreach ($params as $key => $value) {
            $where[] = $key . ' = "' . $sql->quote($value) . '"';
        }
        $req = 'SELECT EXISTS (
                    SELECT *
                    FROM conges_periode
                    WHERE ' . implode(' AND ', $where) . '
        )';

        return 0 < (int) $sql->query($req)->fetch_array()[0];
    }
}
