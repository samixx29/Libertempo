/**
 * Ouvre une popup
 *
 * @param string MyFile   nom du fichier contenant le code HTML du pop-up
 * @param string MyWindow nom de la fenêtre (ne pas mettre d'espace)
 * @param int    MyWidth  entier indiquant la largeur de la fenêtre en pixels
 * @param int    MyHeight entier indiquant la hauteur de la fenêtre en pixels
 */
function OpenPopUp(MyFile,MyWindow,MyWidth,MyHeight)
{
    var ns4 = (document.layers)? true:false; //NS 4
    var ie4 = (document.all)? true:false; //IE 4
    var dom = (document.getElementById)? true:false; //DOM
    var xMax, yMax, xOffset, yOffset;;

    if (ie4 || dom) {
        xMax = screen.width;
        yMax = screen.height;
    } else if (ns4) {
        xMax = window.outerWidth;
        yMax = window.outerHeight;
    } else {
        xMax = 800;
        yMax = 600;
    }
    xOffset = (xMax - MyWidth)/2;
    yOffset = (yMax - MyHeight)/2;
    window.open(MyFile,MyWindow,'width='+MyWidth
        +',height='+MyHeight
        +',screenX='+xOffset
        +',screenY='+yOffset
        +',top='+yOffset
        +',left='+xOffset
        +',scrollbars=yes,resizable=yes');
}

/**
 * Compte le nombre de jours de congés entre deux dates en fonctions du contexte (temps partiel, jours fériés, fermeture, ...)
 */
function compter_jours()
{
    $(document).ready(function () {
        var login = document.forms["dem_conges"].user_login.value;
        var session = document.forms["dem_conges"].session.value;
        var d_debut = document.forms["dem_conges"].new_debut.value;
        var d_fin = document.forms["dem_conges"].new_fin.value;
        var opt_deb = document.forms["dem_conges"].new_demi_jour_deb.value;
        var opt_fin = document.forms["dem_conges"].new_demi_jour_fin.value;
        var p_num = "";

        if( document.forms["dem_conges"].p_num_to_update ) {
            var p_num = document.forms["dem_conges"].p_num_to_update.value;
        }

        if( (d_debut) && (d_fin)) {
            var page = '../calcul_nb_jours_pris.php?session=' + session + '&date_debut=' + d_debut + '&date_fin=' + d_fin+'&user=' + login + '&opt_debut=' +opt_deb + '&opt_fin=' + opt_fin + '&p_num=' +p_num;

            $.ajax({
                type : 'GET',
                url : page,
                dataType : 'text', // expected returned data format.
                success : function(data)
                {
                    var arr = new Array();
                    arr = JSON.parse(data);
                    document.forms["dem_conges"].new_nb_jours.value=arr["nb"];
                    document.getElementById('comment_nbj').innerHTML = arr["comm"];
                },
            });
        }
    });
}

/**
 * Génère le datepicker sur un champ, paramétré par des options précises
 *
 * @param object opts
 */
function generateDatePicker(opts, compter = true)
{
    var defaultOpts = {
        format             : 'dd/mm/yyyy',
        language           : 'fr',
        autoclose          : true,
        todayHighlight     : true,
        daysOfWeekDisabled : [],
        datesDisabled      : [],
        startDate          : ''
    };
    var toApply = defaultOpts;

    /* On ne peut pas écraser une option qui n'existe en défaut */
    for (var i in defaultOpts) {
        toApply[i] = (undefined !== opts[i]) ? opts[i] : defaultOpts[i];
    }
    $(document).ready(function () {
        $('input.date').datepicker(toApply).on("change", function() {
            if( compter == true) {
                compter_jours();
            }
        });
    });
}

/**
 * Génère le timePicker sur un champ, paramétré par des options précises
 *
 * @param string elementId L'id unique du champ sur lequel on applique le timePicker
 * @param object opts
 */
function generateTimePicker(elementId, opts)
{
    var defaultOpts = {
        minuteStep             : 30,
        showInputs             : false,
        showMeridian           : false,
        showWidgetOnAddonClick : false,
        defaultTime            : '12:00',
    };
    var toApply = defaultOpts;

    if (undefined !== opts) {
        /* On ne peut pas écraser une option qui n'existe en défaut */
        for (var i in defaultOpts) {
            toApply[i] = (undefined !== opts[i]) ? opts[i] : defaultOpts[i];
        }
    }

    $('#' + elementId).timepicker(toApply);
}

/**
 * Objet de présentation de l'affichage des semaines
 */
var semaineDisplayer = function (idElement, idCommon, typeSemaines, texts)
{
    this.element      = document.getElementById(idElement);
    this.idCommon     = idCommon;
    this.typeSemaines = typeSemaines;
    this.texts        = texts;

    /**
     * Lance l'initialisation de l'afficheur
     */
    this.init = function ()
    {
        var displayedNot = false;

        for (var idType in this.typeSemaines) {
            if (this.typeSemaines.hasOwnProperty(idType)) {
                if (idType !== this.idCommon) {
                    if (this._hasWeekInputs(this.typeSemaines[idType])) {
                        this._clickedElement(this.element);
                        displayedNot = true;
                        break;
                    }
                }
            }
        }
        if (!displayedNot) {
            this._unclickedElement(this.element);
        }

        /* Événementiel */
        this.element.addEventListener('click', function (e) {
            var element = e.target;
            if (element.classList.contains('active')) {
                this._unclickedElement(element);
            } else {
                this._clickedElement(element);
            }

        }.bind(this));

        return this;
    }

    /**
     * Présente l'élément comme cliqué en affichant les semaines correspondantes
     */
    this._clickedElement = function (element)
    {
        element.classList.add('active');
        element.value = this.texts['common'];
        this._displayNotCommon();
    }

    /**
     * Présente l'élément comme non cliqué en affichant les semaines correspondantes
     */
    this._unclickedElement = function (element)
    {
        element.classList.remove('active');
        element.value = this.texts['notCommon'];
        this._displayCommon();
    }

    /**
     * Vérifie que la semaine a des inputs décisifs en son sein
     *
     * @return bool
     */
    this._hasWeekInputs = function (typeSemaine)
    {
        return 0 < this._getWeekInputs(typeSemaine).length;
    }

    /**
     * Affiche les semaines non communes
     */
    this._displayNotCommon = function ()
    {
        /* Show not common */
        for (var idType in this.typeSemaines) {
            if (this.typeSemaines.hasOwnProperty(idType)) {
                if (idType !== this.idCommon) {
                    var typeSemaine = this.typeSemaines[idType];
                    this._showBlock(typeSemaine);
                }
            }
        }

        /* Hide common */
        this._hideBlock(this.typeSemaines[this.idCommon]);
    }

    /**
     * Affiche les semaines communes
     */
    this._displayCommon = function ()
    {
        /* Show common */
        this._showBlock(this.typeSemaines[this.idCommon]);

        /* Hide not common */
        for (var idType in this.typeSemaines) {
            if (this.typeSemaines.hasOwnProperty(idType)) {
                if (idType !== this.idCommon) {
                    var typeSemaine = this.typeSemaines[idType];
                    this._hideBlock(typeSemaine);
                }
            }
        }
    }

    /**
     * Affiche un bloc et active tous les champs décisifs en son sein
     */
    this._showBlock = function (typeSemaine)
    {
        var inputs = this._getWeekInputs(typeSemaine);
        for (var i = 0; i < inputs.length; ++i) {
            inputs[i].disabled = '';
        }

        document.getElementById(typeSemaine).style.display = 'block';
    }

    /**
     * Cache un bloc et désactive tous les champs décisifs en son sein
     */
    this._hideBlock = function (typeSemaine)
    {
        var inputs = this._getWeekInputs(typeSemaine);
        for (var i = 0; i < inputs.length; ++i) {
            inputs[i].disabled = 'disabled';
        }

        document.getElementById(typeSemaine).style.display = 'none';
    }

    /**
     * Retourne tous les inputs décisifs de la semaine
     *
     * @return Object
     */
    this._getWeekInputs = function (typeSemaine)
    {
        // TODO : not with reference sur un autre form ?
        return document.getElementById(typeSemaine).querySelectorAll('input[type=hidden]');
    }

    /**
     * Lance l'initialisation de l'afficheur en mode lecture seule
     */
    this.readOnly = function ()
    {
        this.element.style.display = 'none';
    }
}

/**
 * Objet de manipulation du planning
 */
var planningController = function (idElement, options, creneaux)
{
    this.element = document.getElementById(idElement);
    this.options = options;
    this.typeSemaine = this.options['typeSemaine'];
    this.debut = this.options['typeHeureDebut'];
    this.fin   = this.options['typeHeureFin'];
    this.incrementMatin = 0;
    this.incrementApresMidi = 0;

    /**
     * Lance l'initialisation de l'afficheur
     */
    this.init = function ()
    {
        this.element.addEventListener('click', function () {
            var select = document.getElementById(this.options['selectJourId']);
            var jourSelectionne = select.options[select.selectedIndex].value;
            var radios = document.body.querySelectorAll('input[type="radio"]');
            var typePeriodeSelected = 0;
            for (var i = 0; i < radios.length; ++i) {
                if (radios[i].checked) {
                    typePeriodeSelected = radios[i].value;
                }
            }
            var debutVal = document.getElementById(this.options['debutId']).value;
            var finVal = document.getElementById(this.options['finId']).value;
            if (this.options['nilInt'] != jourSelectionne && 0 != typePeriodeSelected && '' != debutVal && '' != finVal) {
                if (this._checkTimeValue(debutVal) && this._checkTimeValue(finVal)) {
                    this._emptyHelper();
                    this._addPeriod(jourSelectionne, typePeriodeSelected, debutVal, finVal);
                } else {
                    this._fillHelper(this.options['erreurFormatHeure']);
                }
            } else {
                this._fillHelper(this.options['erreurOptionManquante']);
            }
        }.bind(this));

        /* Remplissage des valeurs préexistantes */
        this._addPeriods(creneaux);
    }

    /**
     * Vide le helper
     */
    this._emptyHelper = function ()
    {
        document.getElementById(this.options['helperId']).innerHTML = '';
    }

    /**
     * Remplis le helper avec le text fourni
     *
     * @param string text
     */
    this._fillHelper = function (text)
    {
        document.getElementById(this.options['helperId']).innerHTML = text;
    }

    /**
     * Vérifie que l'heure respecte bien un format donné
     *
     * @param string timeValue
     *
     * @return bool
     */
    this._checkTimeValue = function (timeValue)
    {
        timeValue   = timeValue.trim();
        var pattern = new RegExp("^(([01]?[0-9])|(2[0-3])):[0-5][0-9]$");

        return pattern.test(timeValue);
    }

    /**
     * Ajoute une liste de périodes au planning
     *
     * @param Object creneaux
     *
     * @return void
     */
    this._addPeriods = function (creneaux)
    {
        for (var jour in creneaux) {
            var dataJour = creneaux[jour];
            for (var periode in dataJour) {
                var dataPeriode = dataJour[periode];
                for (var creneau in dataPeriode) {
                    var dataCreneau = dataPeriode[creneau];
                    if (!dataCreneau.hasOwnProperty(this.debut) || !dataCreneau.hasOwnProperty(this.fin)) {
                        return;
                    }
                    this._addPeriod(jour, periode, dataCreneau[this.debut], dataCreneau[this.fin]);
                }
            }
        }
    }

    /**
     * Ajoute une nouvelle période au planning
     *
     * @param int    jourSelectionne
     * @param int    typePeriodeSelected
     * @param string debutVal
     * @param string finVal
     *
     * @return void
     */
    this._addPeriod = function (jourSelectionne, typePeriodeSelected, debutVal, finVal)
    {
        var table = document.getElementById(this.options['tableId']);
        var ligneCible = table.querySelector('tr[data-id-jour="' +  jourSelectionne + '"]');
        var cellCible = ligneCible.getElementsByClassName('creneaux')[0];

        var periode = this._getVisiblePeriod(jourSelectionne, typePeriodeSelected, debutVal, finVal);

        var allPeriods = ligneCible.querySelectorAll('span[data-heures]');
        if (0 < allPeriods.length) {
            cellCible.insertBefore(periode, this._findSuccPeriod(periode, allPeriods));
        } else {
            cellCible.appendChild(periode);
        }
    }

    /**
     * Retourne la structure DOM d'une période à afficher
     *
     * @param int    jourSelectionne
     * @param int    typePeriodeSelected
     * @param string debutVal
     * @param string finVal
     *
     * @return HTMLSpanElement
     */
    this._getVisiblePeriod = function (jourSelectionne, typePeriodeSelected, debutVal, finVal) {
        var span = document.createElement('span');
        var iBaseTag = document.createElement('i');
        var iTo = iBaseTag.cloneNode(false);
        var buttonTag = document.createElement('button');
        buttonTag.className = 'btn btn-default btn-xs';
        buttonTag.type = 'button';
        buttonTag.addEventListener('click', function (e) {
            this._removePeriod(buttonTag.parentNode);
        }.bind(this));
        iTo.className = 'fa fa-caret-right';
        var debut = document.createTextNode(' ' + debutVal + ' ');
        var labelTypePeriode = (this.options['typePeriodeMatin'] == typePeriodeSelected ) ? 'am' : 'pm';
        var fin   = document.createTextNode(' ' + finVal + ' (' + labelTypePeriode + ') ');
        var iMinus = iBaseTag.cloneNode(false);
        iMinus.className = 'fa fa-minus';
        span.appendChild(debut);
        span.appendChild(iTo);
        span.appendChild(fin);
        buttonTag.appendChild(iMinus);
        span.appendChild(buttonTag);
        span.appendChild(this._getHiddenFieldPeriod(jourSelectionne, typePeriodeSelected, debutVal, finVal));
        /* Positionnement des datasets pour la réorganisation dynamique */
        var patternHour = new RegExp('^[0-9]:[0-5][0-9]$');
        if (patternHour.test(debutVal)) {
            debutVal = '0' + debutVal;
        }
        if (patternHour.test(finVal)) {
            finVal = '0' + finVal;
        }
        span.dataset.heures = debutVal + '-' + finVal;

        return span;
    }

    /**
     * Retourne la structure DOM des champs cachés d'une période à afficher
     *
     * @param int    jourSelectionne
     * @param int    typePeriodeSelected
     * @param string debutVal
     * @param string finVal
     *
     * @return HTMLSpanElement
     */
    this._getHiddenFieldPeriod = function (jourSelectionne, typePeriodeSelected, debutVal, finVal) {
        var span  = document.createElement('span');
        var input = document.createElement('input');
        var debut = input.cloneNode(false);
        var prefixName = 'creneaux[' + this.typeSemaine + '][' + jourSelectionne + '][' + typePeriodeSelected + ']';
        if (this.options['typePeriodeMatin'] == typePeriodeSelected) {
            var prefixName = prefixName + '[' + (++this.incrementMatin) + ']';
        } else {
            var prefixName = prefixName + '[' + (++this.incrementMatin) + ']';
        }
        debut.type  = 'hidden';
        debut.name  = prefixName + '[' + this.debut + ']';
        debut.value = debutVal;
        var fin     = input.cloneNode(false);
        fin.type    = 'hidden';
        fin.name    = prefixName + '[' + this.fin + ']';
        fin.value   = finVal;
        span.appendChild(debut);
        span.appendChild(fin);

        return span;
    }

    /**
     * Supprime une période du planning
     *
     * @return void
     */
    this._removePeriod = function (period)
    {
        period.parentNode.removeChild(period);
    }

    /**
     * Retourne la période suivante à period dans l'ordre lexicographique parmi allPeriods
     *
     * @param HTMLSpanElement period
     * @param NodeListe       allPeriods
     *
     * @return HTMLSpanElement | null
     */
    this._findSuccPeriod = function (period, allPeriods)
    {
        for (var i = 0; i < allPeriods.length; ++i) {
            var brother = allPeriods[i];
            var heurePeriode = period.dataset.heures;
            var heureBrother = brother.dataset.heures;
            if (0 > heurePeriode.localeCompare(heureBrother)) {
                return brother;
            }
        }
    }

    /**
     * Lance l'initialisation de l'afficheur en mode lecture seule
     */
    this.readOnly = function ()
    {
        /* Remplissage des valeurs préexistantes */
        this._addPeriods(creneaux);
        var boutons = document.getElementById(this.options.tableId).querySelectorAll('button');
        for (var i = 0; i < boutons.length; ++i) {
            bouton = boutons[i].style.display = 'none';
        }

        var inputs = document.getElementById(this.options.tableId).querySelectorAll('input');

        for (var i = 0; i < inputs.length; ++i) {
            inputs[i].style.display = 'none';
        }

    }
}


/**
 * Objet de gestion des utilisateurs de planning
 *
 * @param string idElement Identifiant du HTMLElement
 * @param Object associationsGroupe Associations groupes <> utilisateurs
 * @param int nilId Nullité numérique
 */
var selectAssociationPlanning = function (idElement, associationsGroupe, nilId)
{
    this.element = document.getElementById(idElement);
    this.associationsGroupe = associationsGroupe;
    this.nilId = parseInt(nilId);
    this.utilisateurs = document.querySelectorAll('form > div > div.checkbox-utilisateur');

    /**
     * Event
     */
    this.element.addEventListener('change', function (e) {
        var idGroupe = e.target.value;
        if (this.nilId != idGroupe) {
            this._filterUsers(idGroupe);
        } else {
            this._resetFilter();
        }
    }.bind(this));

    /**
     * Filtre les utilisateurs en fonction du groupe passé
     *
     * @param int idGroupe
     */
    this._filterUsers = function (idGroupe)
    {
        this._resetFilter();
        idGroupe = parseInt(idGroupe);
        if (this.associationsGroupe.hasOwnProperty(idGroupe)) {
            var groupe = this.associationsGroupe[idGroupe];
            for (var i = 0; i < this.utilisateurs.length; ++i) {
                var utilisateur = this.utilisateurs[i];
                var utilisateurName = utilisateur.dataset.userLogin;
                if (-1 == groupe.indexOf(utilisateurName)) {
                    utilisateur.style.display = 'none';
                }
            }
        }
    }

    /**
     * Annule tout filtre de groupe
     */
    this._resetFilter = function ()
    {
        for (var i = 0; i < this.utilisateurs.length; ++i) {
            this.utilisateurs[i].style.display = 'block';
        }
    }
}
