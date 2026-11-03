/**
 * Agent Sahbi — widget du service après-vente (module Réclamations & Avis).
 * Le texte des messages est toujours inséré avec textContent : aucun HTML venant du modèle n'est interprété.
 */
(() => {
    const racine = document.getElementById('sahbi');
    if (!racine) return;

    const cfg = racine.dataset;
    const $ = (selecteur) => racine.querySelector(selecteur);
    const lanceur = $('.sahbi-lanceur');
    const panneau = $('.sahbi-panneau');
    const fil = $('.sahbi-fil');
    const zone = $('.sahbi-saisie textarea');
    const envoyer = $('.sahbi-saisie button');
    const suggestions = $('.sahbi-suggestions');
    const statut = $('.sahbi-statut');
    const accroche = $('.sahbi-bulle-accroche');
    const CLE_OUVERT = 'sahbi:ouvert';
    let charge = false;
    let occupe = false;

    const memoire = {
        lire: (cle) => { try { return sessionStorage.getItem(cle); } catch (e) { return null; } },
        ecrire: (cle, valeur) => { try { sessionStorage.setItem(cle, valeur); } catch (e) { /* stockage indisponible */ } },
    };

    const requete = async (url, methode = 'GET', corps = null) => {
        const reponse = await fetch(url, {
            method: methode,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.token, 'X-Requested-With': 'XMLHttpRequest' },
            body: corps ? JSON.stringify(corps) : null,
            credentials: 'same-origin',
        });
        if (reponse.status === 429) throw new Error('Trop de messages en peu de temps : patientez une minute.');
        if (!reponse.ok) throw new Error('Sahbi est momentanément indisponible.');
        return reponse.json();
    };

    // Texte sûr : **gras** et retours à la ligne, rien d'autre
    const remplirTexte = (element, texte) => {
        (texte || '').split(/(\*\*[^*]+\*\*)/g).forEach((morceau) => {
            if (/^\*\*[^*]+\*\*$/.test(morceau)) {
                const gras = document.createElement('strong');
                gras.textContent = morceau.slice(2, -2);
                element.appendChild(gras);
            } else {
                element.appendChild(document.createTextNode(morceau));
            }
        });
    };

    const lienSur = (url) => {
        try { return new URL(url, window.location.origin).origin === window.location.origin; } catch (e) { return false; }
    };

    const defiler = () => { fil.scrollTop = fil.scrollHeight; };

    const carte = (message) => {
        const action = message.action;
        const bloc = document.createElement('div');
        bloc.className = 'sahbi-carte ' + action.statut;
        bloc.dataset.id = message.id;

        const titre = document.createElement('h6');
        titre.textContent = action.titre;
        bloc.appendChild(titre);

        const liste = document.createElement('dl');
        Object.entries(action.details || {}).forEach(([cle, valeur]) => {
            const dt = document.createElement('dt');
            const dd = document.createElement('dd');
            dt.textContent = cle;
            dd.textContent = valeur;
            liste.append(dt, dd);
        });
        bloc.appendChild(liste);

        if (action.statut === 'en_attente') {
            const boutons = document.createElement('div');
            boutons.className = 'boutons';
            [['confirmer', '✓ Confirmer'], ['annuler', 'Annuler']].forEach(([decision, libelle]) => {
                const bouton = document.createElement('button');
                bouton.type = 'button';
                bouton.className = decision;
                bouton.textContent = libelle;
                bouton.addEventListener('click', () => decider(message.id, decision, bloc));
                boutons.appendChild(bouton);
            });
            bloc.appendChild(boutons);
        } else {
            const etat = document.createElement('div');
            etat.className = 'etat';
            etat.textContent = action.statut === 'confirmee' ? '✓ Confirmé' : 'Annulé';
            bloc.appendChild(etat);
        }

        return bloc;
    };

    const afficher = (message) => {
        const bloc = document.createElement('div');
        bloc.className = 'sahbi-msg ' + (message.role === 'user' ? 'user' : 'assistant');

        if (message.contenu) {
            const texte = document.createElement('div');
            texte.className = 'sahbi-texte';
            remplirTexte(texte, message.contenu);
            bloc.appendChild(texte);
        }

        if (message.action) bloc.appendChild(carte(message));

        const liens = (message.liens || []).filter((lien) => lien && lienSur(lien.url));
        if (liens.length) {
            const zoneLiens = document.createElement('div');
            zoneLiens.className = 'sahbi-liens';
            liens.forEach((lien) => {
                const a = document.createElement('a');
                a.href = lien.url;
                a.textContent = lien.label + ' →';
                zoneLiens.appendChild(a);
            });
            bloc.appendChild(zoneLiens);
        }

        if (message.heure) {
            const heure = document.createElement('div');
            heure.className = 'sahbi-heure';
            heure.textContent = message.heure;
            bloc.appendChild(heure);
        }

        fil.appendChild(bloc);
        defiler();
        return bloc;
    };

    const indicateur = () => {
        const ecrit = document.createElement('div');
        ecrit.className = 'sahbi-ecrit';
        ecrit.setAttribute('aria-label', 'Sahbi écrit…');
        ecrit.innerHTML = '<span></span><span></span><span></span>';
        fil.appendChild(ecrit);
        defiler();
        return ecrit;
    };

    const montrerSuggestions = (liste) => {
        suggestions.replaceChildren();
        (liste || []).forEach((texte) => {
            const bouton = document.createElement('button');
            bouton.type = 'button';
            bouton.textContent = texte;
            bouton.addEventListener('click', () => parler(texte));
            suggestions.appendChild(bouton);
        });
        suggestions.hidden = !liste || !liste.length;
    };

    const charger = async () => {
        if (charge) return;
        charge = true;
        try {
            const etat = await requete(cfg.etat);
            fil.replaceChildren();
            afficher({ role: 'assistant', contenu: etat.accueil });
            etat.messages.forEach(afficher);
            montrerSuggestions(etat.messages.length ? [] : etat.suggestions);
            statut.textContent = etat.en_ligne ? 'En ligne · IA DeepSeek' : 'Mode simplifié';
            statut.classList.toggle('hors-ligne', !etat.en_ligne);
        } catch (e) {
            charge = false;
            afficher({ role: 'assistant', contenu: e.message });
        }
    };

    const parler = async (texte) => {
        texte = (texte || '').trim();
        if (!texte || occupe) return;
        occupe = true;
        envoyer.disabled = true;
        montrerSuggestions([]);
        afficher({ role: 'user', contenu: texte, heure: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) });
        zone.value = '';
        const ecrit = indicateur();
        try {
            const donnees = await requete(cfg.message, 'POST', { message: texte });
            ecrit.remove();
            afficher(donnees.message);
        } catch (e) {
            ecrit.remove();
            afficher({ role: 'assistant', contenu: e.message });
        } finally {
            occupe = false;
            envoyer.disabled = false;
            zone.focus();
        }
    };

    const decider = async (id, decision, bloc) => {
        bloc.querySelectorAll('button').forEach((b) => { b.disabled = true; });
        const ecrit = indicateur();
        try {
            const donnees = await requete(cfg.decision.replace('__ID__', id), 'POST', { decision });
            ecrit.remove();
            bloc.replaceWith(carte(donnees.carte));
            if (donnees.message) afficher(donnees.message);
        } catch (e) {
            ecrit.remove();
            bloc.querySelectorAll('button').forEach((b) => { b.disabled = false; });
            afficher({ role: 'assistant', contenu: e.message });
        }
    };

    const ouvrir = (ouvert) => {
        panneau.hidden = !ouvert;
        if (accroche) accroche.hidden = true;
        lanceur.setAttribute('aria-expanded', String(ouvert));
        const pastille = $('.sahbi-pastille');
        if (pastille && ouvert) pastille.remove();
        memoire.ecrire(CLE_OUVERT, ouvert ? '1' : '0');
        if (ouvert) { charger(); setTimeout(() => zone.focus(), 50); }
    };

    lanceur.addEventListener('click', () => ouvrir(panneau.hidden));
    $('.sahbi-fermer').addEventListener('click', () => ouvrir(false));
    if (accroche) accroche.addEventListener('click', () => ouvrir(true));
    $('.sahbi-nouveau').addEventListener('click', async () => {
        if (!confirm('Commencer une nouvelle conversation ?')) return;
        await requete(cfg.reset, 'DELETE').catch(() => null);
        charge = false;
        charger();
    });
    envoyer.addEventListener('click', () => parler(zone.value));
    zone.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); parler(zone.value); }
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panneau.hidden) ouvrir(false); });

    // Bulle d'accroche proactive, une fois par session
    if (accroche && memoire.lire('sahbi:accroche') !== 'vue') {
        setTimeout(() => { if (panneau.hidden) accroche.hidden = false; }, 2500);
        memoire.ecrire('sahbi:accroche', 'vue');
    }

    if (memoire.lire(CLE_OUVERT) === '1') ouvrir(true);
})();
