<?php
namespace App\ProtoControllers\Employe\Heure;

use \App\Models\AHeure;

/**
 * ProtoContrôleur d'heures de repos, en attendant la migration vers le MVC REST
 *
 * @since  1.9
 * @author Prytoegrian <prytoegrian@protonmail.com>
 * @author Wouldsmina
 */
class Repos extends \App\ProtoControllers\Employe\AHeure
{
    /**
     * {@inheritDoc}
     */
    public function getForm($id = NIL_INT)
    {
        $return     = '';
        $errorsLst  = [];
        $valueJour  = date('d/m/Y');
        $valueDebut = '';
        $valueFin   = '';
        $notice = '';
        $comment    = '';

        if (!empty($_POST)) {
            if (0 >= (int) $this->postHtmlCommon($_POST, $errorsLst, $notice)) {
                $errors = '';
                if (!empty($errorsLst)) {
                    foreach ($errorsLst as $key => $value) {
                        if (is_array($value)) {
                            $value = implode(' / ', $value);
                        }
                        $errors .= '<li>' . $key . ' : ' . $value . '</li>';
                    }
                    $return .= '<div class="alert alert-danger">' . _('erreur_recommencer') . '<ul>' . $errors . '</ul></div>';
                }
                $valueJour  = $_POST['jour'];
                $valueDebut = $_POST['debut_heure'];
                $valueFin   = $_POST['fin_heure'];
                $comment    = \includes\SQL::quote($_POST['comment']);
            } else {
                log_action(0, 'demande', '', 'Nouvelle demande d\'heure de repos enregistrée');
                redirect(ROOT_PATH . 'utilisateur/user_index.php?session='. session_id() . '&onglet=liste_heure_repos', false);
            }
        }

        if (NIL_INT !== $id) {
            $return .= '<h1>' . _('user_modif_heure_repos_titre') . '</h1>';
        } else {
            $return .= '<h1>' . _('user_ajout_heure_repos_titre') . '</h1>';
        }

        /* Génération du datePicker et de ses options */
        $daysOfWeekDisabled = \utilisateur\Fonctions::getDatePickerDaysOfWeekDisabled();
        $datesFeries        = \utilisateur\Fonctions::getDatePickerJoursFeries();
        $datesFerme         = \utilisateur\Fonctions::getDatePickerFermeture();
        $datesDisabled      = array_merge($datesFeries,$datesFerme);
        $startDate          = \utilisateur\Fonctions::getDatePickerStartDate();

        $datePickerOpts = [
            'daysOfWeekDisabled' => $daysOfWeekDisabled,
            'datesDisabled'      => $datesDisabled,
            'startDate'          => $startDate,
        ];
        $return .= '<script>generateDatePicker(' . json_encode($datePickerOpts) . ', false);</script>';
        $return .= '<form action="" method="post" class="form-group">';
        $table = new \App\Libraries\Structure\Table();
        $table->addClasses([
            'table',
            'table-hover',
            'table-responsive',
            'table-striped',
            'table-condensed'
        ]);

        $childTable = '';

        if (NIL_INT !== $id) {
            $sql   = 'SELECT * FROM heure_repos WHERE id_heure = ' . $id;
            $query = \includes\SQL::query($sql);
            $data = $query->fetch_array();
            $valueJour  = date('d/m/Y', $data['debut']);
            $valueDebut = date('H\:i', $data['debut']);
            $valueFin   = date('H\:i', $data['fin']);
            $comment    = \includes\SQL::quote($data['comment']);

            $childTable .= '<input type="hidden" name="id_heure" value="' . $id . '" /><input type="hidden" name="_METHOD" value="PUT" />';
        }

        $debutId = uniqid();
        $finId   = uniqid();

        $childTable .= '<thead><tr><th width="20%">' . _('Jour') . '</th>
        <th>' . _('creneau') . '</th><th>' . _('divers_comment_maj_1') . '</th></tr></thead><tbody>';
        $childTable .= '<tr><td><div class="form-inline col-xs-12 col-sm-10 col-lg-8">
        <input class="form-control date" type="text" value="' . $valueJour . '" name="jour"></div></td>';
        $childTable .= '<td><div class="form-inline col-xs-10 col-sm-6 col-lg-4">
        <input class="form-control" style="width:45%" type="text" id="' . $debutId . '"  value="' . $valueDebut . '" name="debut_heure">
        &nbsp;<i class="fa fa-caret-right"></i>&nbsp;
        <input class="form-control" style="width:45%" type="text" id="' . $finId . '"  value="' . $valueFin . '" name="fin_heure"></div></td><td><input class="form-control" type="text" name="comment" value="'.$comment.'" size="20" maxlength="100"></td></tr>';
        $childTable .= '</tbody>';
        $childTable .= '<script type="text/javascript">generateTimePicker("' . $debutId . '");generateTimePicker("' . $finId . '");</script>';

        $table->addChild($childTable);
        ob_start();
        $table->render();
        $return .= ob_get_clean();
        $return .= '<div class="form-group"><input type="submit" class="btn btn-success" value="' . _('form_submit') . '" /></div>';
        $return .='</form>';

        return $return;
    }

    /**
     * {@inheritDoc}
     */
    protected function put(array $put, array &$errorsLst, $user)
    {
        $idHeure = $put['id_heure'];
        if (!$this->hasErreurs($put, $user, $errorsLst, $idHeure)) {
            $data = $this->dataModel2Db($put, $user);
            $id   = $this->update($data, $user, $idHeure);
            log_action($idHeure, 'modif', '', 'Modification demande d\'heure de repos ' . $idHeure);

            return $id;
        }

        return NIL_INT;
    }
    
    
    /**
     * {@inheritDoc}
     */
    protected function post(array $post, array &$errorsLst, $user)
    {
        $return =NIL_INT;
        if (!$this->hasErreurs($post, $user, $errorsLst)) {
            $data = $this->dataModel2Db($post, $user);
            $id   = $this->insert($data, $user);
            log_action($id, 'demande', '', 'demande d\'heure de repos ' . $id);
            $return = $id;

            $notif = new \App\Libraries\Notification\Repos($id);
            if (!$notif->send()) {
                $errorsLst['email'] = _('erreur_envoi_mail');
                $return = NIL_INT;
            }
        }
        return $return;
    }

    /**
     * {@inheritDoc}
     */
    protected function countDuree($debut, $fin, array $planning)
    {
        /*
         * Comme pour le moment on ne peut prendre une heure de repos que sur un jour,
         * on prend arbitrairement le début...
         */
        $numeroSemaine = date('W', $debut);
        $realWeekType  = \utilisateur\Fonctions::getRealWeekType($planning, $numeroSemaine);
        /* Si la semaine n'est pas travaillée */
        if (NIL_INT === $realWeekType) {
            return 0;
        } else {
            /*
             * ... Pareil pour le jour
             */
            $planningWeek = $planning[$realWeekType];
            $jourId = date('N', $debut);
            /* Si le jour n'est pas travaillé */
            if (!\utilisateur\Fonctions::isWorkingDay($planningWeek, $jourId)) {
                return 0;
            } else {
                return $this->countDureeJour($planningWeek[$jourId], $debut, $fin);
            }
        }
    }

    /**
     * Compte la durée réelle de travail à décompter en fonction du planning
     * (Prenez un papier et un crayon pour review / tester ça...)
     *
     * @param array $planningJour Planning de la journée
     * @param int $debut          Timestamp du début de la demande
     * @param int $fin            Timestamp de la fin de la demande
     *
     * @return int
     */
    private function countDureeJour(array $planningJour, $debut, $fin)
    {
        $horodateDebut = \App\Helpers\Formatter::hour2Time(date('H\:i', $debut));
        $horodateFin   = \App\Helpers\Formatter::hour2Time(date('H\:i', $fin));
        $reelleDuree   = 0;

        /* Double foreach pour lisser les créneaux matin / après midi sur le même plan */
        foreach ($planningJour as $creneaux) {
            foreach ($creneaux as $creneau) {
                $creneauDebut = (int) $creneau[\App\Models\Planning\Creneau::TYPE_HEURE_DEBUT];
                $creneauFin   = (int) $creneau[\App\Models\Planning\Creneau::TYPE_HEURE_FIN];

                if ($horodateDebut <= $creneauDebut) {
                    if ($horodateFin <= $creneauDebut) {
                        // On ne cumule rien
                        break;
                    } elseif ($horodateFin > $creneauDebut && $horodateFin <= $creneauFin) {
                        $reelleDuree += $fin - $creneauDebut;
                    } else {
                        /* $horodateFin > $creneauFin */
                        $reelleDuree += $creneauFin - $creneauDebut;
                    }
                } elseif ($horodateDebut > $creneauDebut && $horodateDebut < $creneauFin) {
                    /* $horodateDebut > $creneauDebut */
                    if ($horodateFin > $creneauDebut && $horodateFin <= $creneauFin) {
                        $reelleDuree += $creneauFin - $horodateDebut;
                    } else {
                        /* $horodateFin > $creneauFin */
                        $reelleDuree += $creneauFin - $horodateDebut;
                    }
                }
            }
        }

        return $reelleDuree;
    }

    /**
     * {@inheritDoc}
     */
    protected function delete($id, $user, array &$errorsLst, &$notice)
    {
        $return = NIL_INT;
        if (NIL_INT !== $this->deleteSQL($id, $user, $errorsLst)) {
            log_action($id, 'annul', '', 'Annulation de la demande d\'heure de repos ' . $id);
            $notice = _('heure_repos_annulee');
            return $id;

            $notif = new \App\Libraries\Notification\repos($id);
            if (!$notif->send()) {
                $errorsLst['email'] = _('erreur_envoi_mail');
                $return = NIL_INT;
            }
        }
        return $return;
    }

    /**
     * {@inheritDoc}
     */
    public function getListe()
    {
        $message   = '';
        $errorsLst = [];
        $notice    = '';
        if (!empty($_POST) && !$this->isSearch($_POST)) {
            if (0 >= (int) $this->postHtmlCommon($_POST, $errorsLst, $notice)) {
                $errors = '';
                if (!empty($errorsLst)) {
                    foreach ($errorsLst as $value) {
                        $errors .= '<li>' . $value . '</li>';
                    }
                    $message = '<div class="alert alert-danger">' . _('erreur_recommencer') . ' :<ul>' . $errors . '</ul></div>';
                }
            } elseif ('DELETE' === $_POST['_METHOD'] && !empty($notice)) {
                log_action(0, '', '', 'Annulation de l\'heure de repos ' . $_POST['id_heure']);
                $message = '<div class="alert alert-info">' .  $notice . '.</div>';
            } else {
                log_action(0, '', '', 'Récupération de l\'heure de repos ' . $_POST['id_heure']);
                redirect(ROOT_PATH . 'utilisateur/user_index.php?session='. session_id() . '&onglet=liste_heure_repos', false);
            }
        }
        if (!empty($_POST) && $this->isSearch($_POST)) {
            $champsRecherche = $_POST['search'];
            $champsSql       = $this->transformChampsRecherche($_POST);
        } else {
            $champsRecherche = [];
            $champsSql       = [];
        }
        $params = $champsSql + [
            'login' => $_SESSION['userlogin'],
        ];

        $return = '<h1>' . _('user_liste_heure_repos_titre') . '</h1>';
        $return .= $this->getFormulaireRecherche($champsRecherche);
        $return .= $message;
        $table = new \App\Libraries\Structure\Table();
        $table->addClasses([
            'table',
            'table-hover',
            'table-responsive',
            'table-condensed',
            'table-striped',
        ]);
        $childTable = '<thead><tr><th>' . _('jour') . '</th><th>' . _('divers_debut_maj_1') . '</th><th>' . _('divers_fin_maj_1') . '</th><th>' . _('duree') . '</th><th>' . _('statut') . '</th><th>' . _('commentaire') . '</th><th></th></tr></thead><tbody>';
        $session = session_id();
        $listId = $this->getListeId($params);
        if (empty($listId)) {
            $childTable .= '<tr><td colspan="6"><center>' . _('aucun_resultat') . '</center></td></tr>';
        } else {
            $listeRepos = $this->getListeSQL($listId);
            foreach ($listeRepos as $repos) {
                $jour   = date('d/m/Y', $repos['debut']);
                $debut  = date('H\:i', $repos['debut']);
                $fin    = date('H\:i', $repos['fin']);
                $duree  = date('H\:i', $repos['duree']);
                $statut = AHeure::statusText($repos['statut']);
                $comment = \includes\SQL::quote($repos['comment']);
                if (AHeure::STATUT_DEMANDE == $repos['statut']) {
                    $modification = '<a title="' . _('form_modif') . '" href="user_index.php?onglet=modif_heure_repos&id=' . $repos['id_heure'] . '&session=' . $session . '"><i class="fa fa-pencil"></i></a>';
                    $annulation   = '<input type="hidden" name="id_heure" value="' . $repos['id_heure'] . '" /><input type="hidden" name="_METHOD" value="DELETE" /><button type="submit" class="btn btn-link" title="' . _('Annuler') . '"><i class="fa fa-times-circle"></i></button>';
                } else {
                    $modification = '<i class="fa fa-pencil disabled" title="'  . _('heure_non_modifiable') . '"></i>';
                    $annulation   = '<button title="' . _('heure_non_supprimable') . '" type="button" class="btn btn-link disabled"><i class="fa fa-times-circle"></i></button>';
                }
                $childTable .= '<tr><td>' . $jour . '</td><td>' . $debut . '</td><td>' . $fin . '</td><td>' . $duree . '</td><td>' . $statut . '</td><td>' . $comment . '</td><td><form action="" method="post" accept-charset="UTF-8"
enctype="application/x-www-form-urlencoded">' . $modification . '&nbsp;&nbsp;' . $annulation . '</form></td></tr>';
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
     * {@inheritDoc}
     */
    protected function getFormulaireRecherche(array $champs)
    {
        $form = '<form method="post" action="" class="form-inline search" role="form"><div class="form-group"><label class="control-label col-md-4" for="statut">Statut&nbsp;:</label><div class="col-md-8"><select class="form-control" name="search[statut]" id="statut">';
        foreach (\App\Models\AHeure::getOptionsStatuts() as $key => $value) {
            $selected = (isset($champs['statut']) && $key == $champs['statut'])
                ? 'selected="selected"'
                : '';
            $form .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
        }
        $form .= '</select></div></div><div class="form-group"><label class="control-label col-md-4" for="annee">Année&nbsp;:</label><div class="col-md-8"><select class="form-control" name="search[annee]" id="sel1">';
        foreach (\utilisateur\Fonctions::getOptionsAnnees() as $key => $value) {
            $selected = (isset($champs['annee']) && $key == $champs['annee'])
                ? 'selected="selected"'
                : '';
            $form .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
        }
        $form .= '</select></div></div><div class="form-group"><div class="input-group"><button type="submit" class="btn btn-default"><i class="fa fa-search" aria-hidden="true"></i></button>&nbsp;<a href="' . ROOT_PATH . 'utilisateur/user_index.php?session='. session_id() . '&onglet=liste_heure_repos" type="reset" class="btn btn-default">Reset</a></div></div></form>';

        return $form;
    }

    /*
     * SQL
     */

    /**
     * {@inheritDoc}
     */
    protected function getListeId(array $params)
    {
        if (!empty($params)) {
            $where = [];
            foreach ($params as $key => $value) {
                switch ($key) {
                    case 'timestampDebut':
                        $where[] = 'debut >= ' . $value;
                        break;
                    case 'timestampFin':
                        $where[] = 'debut <= ' . $value;
                        break;
                    default:
                        $where[] = $key . ' = "' . $value . '"';
                        break;
                }
            }
        }
        $ids = [];
        $sql = \includes\SQL::singleton();
        $req = 'SELECT id_heure AS id
                FROM heure_repos '
                . ((!empty($where)) ? ' WHERE ' . implode(' AND ', $where) : '');
        $res = $sql->query($req);
        while ($data = $res->fetch_array()) {
            $ids[] = (int) $data['id'];
        }

        return $ids;
    }

    /**
     * {@inheritDoc}
     */
    protected function getListeSQL(array $listId)
    {
        if (empty($listId)) {
            return [];
        }
        $sql = \includes\SQL::singleton();
        $req = 'SELECT *
                FROM heure_repos
                WHERE id_heure IN (' . implode(',', $listId) . ')
                ORDER BY debut DESC, statut ASC';

        return $sql->query($req)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * {@inheritDoc}
     */
    protected function isChevauchement($jour, $heureDebut, $heureFin, $id, $user)
    {
        $jour = \App\Helpers\Formatter::dateFr2Iso($jour);
        $timestampDebut = strtotime($jour . ' ' . $heureDebut);
        $timestampFin   = strtotime($jour . ' ' . $heureFin);
        $statuts = [
            AHeure::STATUT_DEMANDE,
            AHeure::STATUT_PREMIERE_VALIDATION,
            AHeure::STATUT_VALIDATION_FINALE,
        ];

        $sql = \includes\SQL::singleton();
        $req = 'SELECT EXISTS (SELECT statut
                FROM heure_repos
                WHERE login = "' . $user . '"
                    AND statut IN (' . implode(',', $statuts) . ')
                    AND (debut <= ' . $timestampFin . ' AND fin >= ' . $timestampDebut . ')';
        if (NIL_INT !== $id) {
            $req .= ' AND id_heure !=' . $id;
        }
        $req .= ')';
        $query = $sql->query($req);

        return 0 < (int) $query->fetch_array()[0];
    }

    /**
     * {@inheritDoc}
     */
    protected function insert(array $data, $user)
    {
        $sql = \includes\SQL::singleton();
        $req = 'INSERT INTO heure_repos (id_heure, login, debut, fin, duree, statut, comment) VALUES
        (NULL, "' . $user . '", ' . (int) $data['debut'] . ', '. (int) $data['fin'] .', '. (int) $data['duree'] . ', ' . AHeure::STATUT_DEMANDE . ', "' . \includes\SQL::quote($data['comment']) . '")';
        $query = $sql->query($req);

        return $sql->insert_id;
    }

    /**
     * {@inheritDoc}
     */
    protected function update(array $data, $user, $id)
    {
        $sql   = \includes\SQL::singleton();
        $toInsert = [];
        $req   = 'UPDATE heure_repos
                SET debut = ' . $data['debut'] . ',
                    fin = ' . $data['fin'] . ',
                    duree = ' . $data['duree'] . ',
                    comment = \''.$data['comment'].'\'
                WHERE id_heure = '. (int) $id . '
                AND login = "' . $user . '"';
        $query = $sql->query($req);

        return $id;
    }

    /**
     * {@inheritDoc}
     */
    protected function deleteSQL($id, $user)
    {
        $sql = \includes\SQL::singleton();
        $req = 'UPDATE heure_repos
                SET statut = ' . AHeure::STATUT_ANNUL . '
                WHERE id_heure = ' . (int) $id . '
                AND login = "' . $user . '"';
        $sql->query($req);

        return 0 < $sql->affected_rows ? $id : NIL_INT;
    }

    /**
     * {@inheritDoc}
     */
    public function canUserEdit($id, $user)
    {
        $sql = \includes\SQL::singleton();
        $req = 'SELECT EXISTS (
                    SELECT id_heure
                    FROM heure_repos
                    WHERE id_heure = ' . (int) $id . '
                        AND statut = ' . AHeure::STATUT_DEMANDE . '
                        AND login = "' . $user . '"
                )';
        $query = $sql->query($req);

        return 0 < (int) $query->fetch_array()[0];
    }

    /**
     * {@inheritDoc}
     */
    public function canUserDelete($id, $user)
    {
        $sql = \includes\SQL::singleton();
        $req = 'SELECT EXISTS (
                    SELECT id_heure
                    FROM heure_repos
                    WHERE id_heure = ' . (int) $id . '
                        AND statut = ' . AHeure::STATUT_DEMANDE . '
                        AND login = "' . $user . '"
                )';
        $query = $sql->query($req);

        return 0 < (int) $query->fetch_array()[0];
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
                    FROM heure_repos
                    WHERE ' . implode(' AND ', $where) . '
        )';

        return 0 < (int) $sql->query($req)->fetch_array()[0];
    }
}
