/*
 script.js
 Le JavaScript est utilisé uniquement pour les popups (humeur et niveau).
 Toutes les actions fonctionnelles (cocher les quêtes, changer l'humeur)
 passent par des formulaires HTML classiques en POST.
*/

document.addEventListener('DOMContentLoaded', function() {

    /* Popup humeur : fermeture si on clique en dehors du contenu */
    var popupHumeur = document.getElementById('popup_humeur');
    if (popupHumeur) {
        popupHumeur.addEventListener('click', function(e) {
            if (e.target === popupHumeur) {
                popupHumeur.style.display = 'none';
            }
        });
    }

    /* Popup niveau : affichage automatique si un passage de niveau vient d'avoir lieu */
    if (typeof niveauPrecedent !== 'undefined' && typeof niveauActuel !== 'undefined') {
        if (niveauActuel > niveauPrecedent) {
            var titres = {
                5: "Apprenti",   10: "Explorateur", 20: "Aventurier",
                35: "Guerrier",  50: "Héros",       75: "Légende",
                100: "Maître",  150: "Dieu du quotidien"
            };
            var msg = "Tu es maintenant niveau " + niveauActuel + " !";
            if (titres[niveauActuel]) {
                msg += " Tu as débloqué le titre : " + titres[niveauActuel];
            }
            document.getElementById('popup_message').innerText = msg;
            document.getElementById('popup_niveau').style.display = 'flex';
        }
    }

    /* Profil : soumet le formulaire avatar dès qu'un radio change */
    document.querySelectorAll('.avatar_input_cache').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var form = document.getElementById('form_avatar');
            if (form) form.submit();
        });
    });

});
