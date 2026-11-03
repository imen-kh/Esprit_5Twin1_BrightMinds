<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\EtapeTracabiliteController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\Acteur\AgriWeatherController;
use App\Http\Controllers\Acteur\HarvestController as ActeurHarvestController;
use App\Http\Controllers\Acteur\StockController as ActeurStockController;
use App\Http\Controllers\Admin\HarvestController as AdminHarvestController;
use App\Http\Controllers\Admin\StockController as AdminStockController;
use App\Http\Controllers\Acteur\CategorySuggestionController;
use App\Http\Controllers\Acteur\DashboardController as ActeurDashboardController;
use App\Http\Controllers\Acteur\EspaceController;
use App\Http\Controllers\Acteur\ProductController as ActeurProductController;
use App\Http\Controllers\PanierController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\DoubleAuthentificationController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CommandeController as AdminCommandeController;
use App\Http\Controllers\Admin\LivraisonController as AdminLivraisonController;
use App\Http\Controllers\Distributeur\CommandeController as DistributeurCommandeController;
use App\Http\Controllers\Distributeur\LivraisonController as DistributeurLivraisonController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TracabiliteController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\Admin\CertificationController as AdminCertificationController;
use App\Http\Controllers\Admin\LabelController as AdminLabelController;
use App\Http\Controllers\Acteur\CertificationController as ActeurCertificationController;
use App\Http\Controllers\PublicLabelController;
use App\Http\Controllers\Back\FacteurEmissionController;
use App\Http\Controllers\Back\BilanEnvironnementalController;
use App\Http\Controllers\Front\EnvironnementController;
use App\Http\Controllers\Front\ReclamationController as FrontReclamationController;
use App\Http\Controllers\Back\ReclamationController as BackReclamationController;
use App\Http\Controllers\ReponseReclamationController;
use App\Http\Controllers\Front\AvisController as FrontAvisController;
use App\Http\Controllers\Back\AvisController as BackAvisController;
use App\Http\Controllers\Back\AlerteQualiteController;
use App\Http\Controllers\Acteur\RetourClientController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// ===== Front office (Fruitables) =====
Route::get('/', [CatalogueController::class, 'home'])->name('home');
Route::middleware('compte')->group(function () {
    Route::get('/produits', [CatalogueController::class, 'index'])->name('produits');
    Route::get('/produits/suggestions', [CatalogueController::class, 'suggest'])->name('produits.suggestions');
    Route::get('/produits/{product}', [CatalogueController::class, 'show'])->name('produit.detail');
    // Module Commandes : panier et passage de commande
    Route::get('/panier', [PanierController::class, 'index'])->name('panier');
    Route::post('/panier/add', [PanierController::class, 'add'])->name('panier.add');
    Route::put('/panier/{product_id}', [PanierController::class, 'update'])->name('panier.update');
    Route::delete('/panier/{product_id}', [PanierController::class, 'remove'])->name('panier.remove');
    Route::get('/commande', [CommandeController::class, 'create'])->middleware('auth')->name('commande');
    Route::post('/commande', [CommandeController::class, 'store'])->middleware('auth')->name('commande.store');
    Route::view('/temoignages', 'front.temoignages')->name('temoignages');
});
Route::view('/contact', 'front.contact')->name('contact');
Route::view('/404', 'errors.404')->name('page404');

// Module Traçabilité : pages publiques (sans connexion), accessibles par QR code
Route::get('/tracabilite', [TracabiliteController::class, 'index'])->name('tracabilite.index');
Route::get('/tracabilite/{lot:code}', [TracabiliteController::class, 'show'])->name('tracabilite.show');
Route::get('/tracabilite/{lot:code}/pdf', [TracabiliteController::class, 'pdf'])->name('tracabilite.pdf');
// Assistant IA de la page publique : 10 questions par minute et par visiteur
Route::post('/tracabilite/{lot:code}/assistant', [TracabiliteController::class, 'assistant'])
    ->middleware('throttle:10,1')
    ->name('tracabilite.assistant');
// Module Environnement — front-office
// Les segments fixes (simuler, create) sont déclarés avant /{bilan}.
Route::get('/environnement', [EnvironnementController::class, 'index'])->name('front.environnement.index');
Route::get('/environnement/comparer', [EnvironnementController::class, 'comparer'])->name('front.environnement.comparer');
Route::post('/environnement/simuler', [EnvironnementController::class, 'simuler'])->name('front.environnement.simuler');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/environnement/create', [EnvironnementController::class, 'create'])->name('front.environnement.create');
    Route::post('/environnement', [EnvironnementController::class, 'store'])->name('front.environnement.store');
    Route::get('/environnement/{bilan}/edit', [EnvironnementController::class, 'edit'])->name('front.environnement.edit');
    Route::match(['put', 'patch'], '/environnement/{bilan}', [EnvironnementController::class, 'update'])->name('front.environnement.update');
    Route::delete('/environnement/{bilan}', [EnvironnementController::class, 'destroy'])->name('front.environnement.destroy');
});

Route::get('/environnement/{bilan}', [EnvironnementController::class, 'show'])->name('front.environnement.show');

// ===== Authentification =====
// Pages réservées aux visiteurs non connectés
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

    // Mot de passe oublié (broker natif de Laravel) : lien par email, puis choix du nouveau mot de passe
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    // Limiteurs nommés « mot-de-passe », « deux-facteurs »… : voir AppServiceProvider
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:mot-de-passe')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:mot-de-passe')->name('password.update');

    // Double authentification (TOTP) : code de l'application ou code de récupération, après le mot de passe
    Route::get('/two-factor-challenge', [TwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verify'])->middleware('throttle:deux-facteurs')->name('two-factor.verify');
});

// ===== Vérification de l'adresse email (utilisateur connecté, adresse non encore confirmée) =====
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:verification-email'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:verification-email')
        ->name('verification.send');
});

// Déconnexion : le bouton envoie un POST ; le GET évite l'erreur 405 quand on saisit /logout
// dans la barre d'adresse. Sans le middleware 'auth' : une session déjà expirée renvoie
// simplement vers la page de connexion.
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// ===== Mon profil (tous les rôles connectés, adresse email vérifiée) =====
Route::middleware(['auth', 'verified'])->group(function () {
    // ===== Module Réclamations & Avis (client, acteur vendeur, admin : droits dans les Policies) =====
    Route::resource('reclamations', FrontReclamationController::class);
    Route::post('reclamations/{reclamation}/reponses', [ReponseReclamationController::class, 'store'])->name('reclamations.reponses.store');
    Route::patch('reclamations/{reclamation}/cloturer', [FrontReclamationController::class, 'cloturer'])->name('reclamations.cloturer');
    Route::post('reclamations/{reclamation}/evaluer', [FrontReclamationController::class, 'evaluer'])->name('reclamations.evaluer');
    Route::post('reclamations/{reclamation}/relancer', [FrontReclamationController::class, 'relancer'])->name('reclamations.relancer');

    Route::get('mes-avis', [FrontAvisController::class, 'index'])->name('avis.index');
    Route::post('produits/{product}/avis', [FrontAvisController::class, 'store'])->middleware('throttle:10,1')->name('avis.store');
    Route::get('avis/{avis}/modifier', [FrontAvisController::class, 'edit'])->name('avis.edit');
    Route::put('avis/{avis}', [FrontAvisController::class, 'update'])->name('avis.update');
    Route::delete('avis/{avis}', [FrontAvisController::class, 'destroy'])->name('avis.destroy');
    Route::post('avis/{avis}/reagir', [FrontAvisController::class, 'reagir'])->middleware('throttle:30,1')->name('avis.reagir');
    Route::post('avis/{avis}/reclamation', [FrontAvisController::class, 'convertir'])->name('avis.convertir');

    // Agent Sahbi : 20 messages par minute et par utilisateur
    Route::get('assistant', [AssistantController::class, 'etat'])->name('assistant.etat');
    Route::post('assistant/message', [AssistantController::class, 'message'])->middleware('throttle:20,1')->name('assistant.message');
    Route::post('assistant/messages/{message}/decision', [AssistantController::class, 'decider'])->middleware('throttle:20,1')->name('assistant.decider');
    Route::delete('assistant', [AssistantController::class, 'reinitialiser'])->name('assistant.reinitialiser');

    Route::get('notifications/{id}', [NotificationController::class, 'lire'])->name('notifications.lire');
    Route::post('notifications/tout-lire', [NotificationController::class, 'toutLire'])->name('notifications.tout-lire');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::put('/profil/mot-de-passe', [ProfileController::class, 'updatePassword'])->name('profil.password');
    // Double authentification par application (TOTP)
    Route::post('/profil/double-authentification', [DoubleAuthentificationController::class, 'activer'])->name('profil.two-factor.activer');
    Route::post('/profil/double-authentification/confirmer', [DoubleAuthentificationController::class, 'confirmer'])->middleware('throttle:activation-deux-facteurs')->name('profil.two-factor.confirmer');
    Route::post('/profil/double-authentification/codes', [DoubleAuthentificationController::class, 'regenererCodes'])->name('profil.two-factor.codes');
    Route::delete('/profil/double-authentification/annuler', [DoubleAuthentificationController::class, 'annuler'])->name('profil.two-factor.annuler');
    Route::delete('/profil/double-authentification', [DoubleAuthentificationController::class, 'desactiver'])->name('profil.two-factor.desactiver');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profil.destroy');
    Route::get('/mes-commandes', [CommandeController::class, 'index'])->name('commandes.index');
    Route::get('/mes-commandes/create', [CommandeController::class, 'create'])->name('commandes.create');
    Route::get('/mes-commandes/{commande}', [CommandeController::class, 'show'])->name('commandes.show');
});

Route::middleware(['auth', 'verified', 'acteur'])->prefix('acteur')->name('acteur.')->group(function () {
    Route::get('/transformation', [EspaceController::class, 'transformation'])->name('transformation');
    Route::get('/distribution', [EspaceController::class, 'distribution'])->name('distribution');
    Route::post('/categories/suggerer', [CategorySuggestionController::class, 'store'])->name('categories.suggerer');
    Route::post('produits/generer-ia', [ActeurProductController::class, 'genererIa'])
        ->middleware(['permission:gerer-produits', 'throttle:10,1'])
        ->name('produits.generer-ia');
    Route::post('produits/qualite-ia', [ActeurProductController::class, 'qualiteIa'])
        ->middleware(['permission:gerer-produits', 'throttle:10,1'])
        ->name('produits.qualite-ia');
    Route::get('meteo', [AgriWeatherController::class, 'index'])
        ->middleware('permission:gerer-produits')
        ->name('meteo');
    Route::middleware('permission:gerer-produits')->group(function () {
        Route::get('tableau', [ActeurDashboardController::class, 'index'])->name('tableau');
        Route::get('recoltes/evenements', [ActeurHarvestController::class, 'events'])->name('recoltes.evenements');
        Route::resource('recoltes', ActeurHarvestController::class)->parameters(['recoltes' => 'harvest']);
        Route::get('stocks', [ActeurStockController::class, 'index'])->name('stocks.index');
        Route::get('stocks/creer', [ActeurStockController::class, 'create'])->name('stocks.create');
        Route::post('stocks', [ActeurStockController::class, 'store'])->name('stocks.store');
        Route::get('stocks/{stockItem}', [ActeurStockController::class, 'show'])->name('stocks.show');
        Route::patch('stocks/{stockItem}', [ActeurStockController::class, 'update'])->name('stocks.update');
        Route::post('stocks/{stockItem}/mouvements', [ActeurStockController::class, 'mouvement'])->name('stocks.mouvements.store');
    });
    Route::resource('produits', ActeurProductController::class)->parameters(['produits' => 'product'])->except(['show']);
    // Module Réclamations & Avis : retours des clients sur les produits de l'acteur
    Route::get('retours/reclamations', [RetourClientController::class, 'reclamations'])->name('reclamations.index');
    Route::get('retours/avis', [RetourClientController::class, 'avis'])->name('avis.index');
    Route::post('retours/avis/{avis}/reponse', [RetourClientController::class, 'repondreAvis'])->middleware('permission:repondre-avis')->name('avis.repondre');
});

// ===== Module Traçabilité : gestion des lots et de leurs étapes (acteurs et administrateurs) =====
Route::middleware(['auth', 'verified', 'acteur', 'permission:gerer-lots'])->prefix('gestion')->name('gestion.')->group(function () {
    Route::resource('lots', LotController::class);
    // IA du module : histoire du produit (page publique) et analyse des anomalies
    Route::post('lots/{lot}/histoire', [LotController::class, 'regenererHistoire'])->name('lots.histoire');
    Route::post('lots/{lot}/analyser', [LotController::class, 'analyser'])->name('lots.analyser');
    Route::resource('etapes', EtapeTracabiliteController::class);
});

// ===== Labels et certifications =====
Route::get('/labels', [PublicLabelController::class, 'index'])->name('labels.index');
Route::get('/labels/{label}', [PublicLabelController::class, 'show'])->name('labels.show');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('labels/create', [AdminLabelController::class, 'create'])->name('labels.create');
    Route::get('labels/{label}/edit', [AdminLabelController::class, 'edit'])->name('labels.edit');
    Route::apiResource('labels', AdminLabelController::class)->except(['create', 'edit']);
    Route::get('certifications/create', [AdminCertificationController::class, 'create'])->name('certifications.create');
    Route::post('certifications', [AdminCertificationController::class, 'store'])->name('certifications.store');
    Route::get('certifications', [AdminCertificationController::class, 'index'])->name('certifications.index');
    Route::get('certifications/{certification}', [AdminCertificationController::class, 'show'])->name('certifications.show');
    Route::post('certifications/{certification}/analyser', [AdminCertificationController::class, 'analyser'])->middleware('permission:valider-certifications')->name('certifications.analyser');
    Route::get('certifications-expiring', [AdminCertificationController::class, 'expiring'])->name('certifications.expiring');
    Route::patch('certifications/{certification}/validate', [AdminCertificationController::class, 'validate'])->middleware('permission:valider-certifications')->name('certifications.validate');
    Route::delete('certifications/{certification}', [AdminCertificationController::class, 'destroy'])->name('certifications.destroy');
});

Route::prefix('acteur')->name('acteur.')->middleware(['auth', 'verified', 'acteur'])->group(function () {
    Route::get('certifications/create', [ActeurCertificationController::class, 'create'])->name('certifications.create');
    Route::get('certifications-expiring', [ActeurCertificationController::class, 'expiring'])->name('certifications.expiring');
    Route::apiResource('certifications', ActeurCertificationController::class)->except(['create', 'edit']);
    Route::post('certifications/{certification}/analyser', [ActeurCertificationController::class, 'analyser'])->name('certifications.analyser');
    Route::get('certifications/{certification}/edit', [ActeurCertificationController::class, 'edit'])->name('certifications.edit');
});

// ===== Module Commandes : espace du distributeur (commandes et livraisons) =====
Route::middleware(['auth', 'verified', 'distributeur', 'permission:gerer-livraisons'])->prefix('distributeur')->name('distributeur.')->group(function () {
    Route::get('/', [DistributeurLivraisonController::class, 'dashboard'])->name('dashboard');
    Route::get('/commandes', [DistributeurCommandeController::class, 'index'])->name('commandes.index');
    Route::get('/commandes/{commande}', [DistributeurCommandeController::class, 'show'])->name('commandes.show');
    Route::patch('/commandes/{commande}/prendre-en-charge', [DistributeurCommandeController::class, 'prendreEnCharge'])->name('commandes.prendre-en-charge');
    Route::patch('/commandes/{commande}/preparer', [DistributeurCommandeController::class, 'preparer'])->name('commandes.preparer');
    Route::get('/commandes/{commande}/livraison/create', [DistributeurLivraisonController::class, 'create'])->name('livraisons.create');
    Route::post('/commandes/{commande}/livraison', [DistributeurLivraisonController::class, 'store'])->name('livraisons.store');
    Route::get('/livraisons', [DistributeurLivraisonController::class, 'index'])->name('livraisons.index');
    Route::get('/livraisons/{livraison}', [DistributeurLivraisonController::class, 'show'])->name('livraisons.show');
    Route::get('/livraisons/{livraison}/edit', [DistributeurLivraisonController::class, 'edit'])->name('livraisons.edit');
    Route::put('/livraisons/{livraison}', [DistributeurLivraisonController::class, 'update'])->name('livraisons.update');
    Route::patch('/livraisons/{livraison}/statut', [DistributeurLivraisonController::class, 'updateStatus'])->name('livraisons.statut');
    Route::patch('/livraisons/{livraison}/expedier', [DistributeurLivraisonController::class, 'expedier'])->name('livraisons.expedier');
    Route::patch('/livraisons/{livraison}/transit', [DistributeurLivraisonController::class, 'mettreEnTransit'])->name('livraisons.transit');
    Route::patch('/livraisons/{livraison}/livrer', [DistributeurLivraisonController::class, 'livrer'])->name('livraisons.livrer');
});

// ===== Back office (Sneat) : réservé aux administrateurs connectés =====
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::view('/auth-forgot-password-basic', 'back.auth-forgot-password-basic')->name('auth-forgot-password-basic');
    Route::view('/auth-login-basic', 'back.auth-login-basic')->name('auth-login-basic');
    Route::view('/auth-register-basic', 'back.auth-register-basic')->name('auth-register-basic');
    Route::view('/cards-basic', 'back.cards-basic')->name('cards-basic');
    Route::view('/extended-ui-perfect-scrollbar', 'back.extended-ui-perfect-scrollbar')->name('extended-ui-perfect-scrollbar');
    Route::view('/extended-ui-text-divider', 'back.extended-ui-text-divider')->name('extended-ui-text-divider');
    Route::view('/form-layouts-horizontal', 'back.form-layouts-horizontal')->name('form-layouts-horizontal');
    Route::view('/form-layouts-vertical', 'back.form-layouts-vertical')->name('form-layouts-vertical');
    Route::view('/forms-basic-inputs', 'back.forms-basic-inputs')->name('forms-basic-inputs');
    Route::view('/forms-input-groups', 'back.forms-input-groups')->name('forms-input-groups');
    Route::view('/icons-boxicons', 'back.icons-boxicons')->name('icons-boxicons');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('recoltes', [AdminHarvestController::class, 'index'])->name('recoltes.index');
    Route::get('stocks', [AdminStockController::class, 'index'])->name('stocks.index');
    Route::view('/layouts-blank', 'back.layouts-blank')->name('layouts-blank');
    Route::view('/layouts-container', 'back.layouts-container')->name('layouts-container');
    Route::view('/layouts-fluid', 'back.layouts-fluid')->name('layouts-fluid');
    Route::view('/layouts-without-menu', 'back.layouts-without-menu')->name('layouts-without-menu');
    Route::view('/layouts-without-navbar', 'back.layouts-without-navbar')->name('layouts-without-navbar');
    Route::view('/pages-account-settings-account', 'back.pages-account-settings-account')->name('pages-account-settings-account');
    Route::view('/pages-account-settings-connections', 'back.pages-account-settings-connections')->name('pages-account-settings-connections');
    Route::view('/pages-account-settings-notifications', 'back.pages-account-settings-notifications')->name('pages-account-settings-notifications');
    Route::view('/pages-misc-error', 'back.pages-misc-error')->name('pages-misc-error');
    Route::view('/pages-misc-under-maintenance', 'back.pages-misc-under-maintenance')->name('pages-misc-under-maintenance');
    Route::view('/tables-basic', 'back.tables-basic')->name('tables-basic');
    Route::view('/ui-accordion', 'back.ui-accordion')->name('ui-accordion');
    Route::view('/ui-alerts', 'back.ui-alerts')->name('ui-alerts');
    Route::view('/ui-badges', 'back.ui-badges')->name('ui-badges');
    Route::view('/ui-buttons', 'back.ui-buttons')->name('ui-buttons');
    Route::view('/ui-carousel', 'back.ui-carousel')->name('ui-carousel');
    Route::view('/ui-collapse', 'back.ui-collapse')->name('ui-collapse');
    Route::view('/ui-dropdowns', 'back.ui-dropdowns')->name('ui-dropdowns');
    Route::view('/ui-footer', 'back.ui-footer')->name('ui-footer');
    Route::view('/ui-list-groups', 'back.ui-list-groups')->name('ui-list-groups');
    Route::view('/ui-modals', 'back.ui-modals')->name('ui-modals');
    Route::view('/ui-navbar', 'back.ui-navbar')->name('ui-navbar');
    Route::view('/ui-offcanvas', 'back.ui-offcanvas')->name('ui-offcanvas');
    Route::view('/ui-pagination-breadcrumbs', 'back.ui-pagination-breadcrumbs')->name('ui-pagination-breadcrumbs');
    Route::view('/ui-progress', 'back.ui-progress')->name('ui-progress');
    Route::view('/ui-spinners', 'back.ui-spinners')->name('ui-spinners');
    Route::view('/ui-tabs-pills', 'back.ui-tabs-pills')->name('ui-tabs-pills');
    Route::view('/ui-toasts', 'back.ui-toasts')->name('ui-toasts');
    Route::view('/ui-tooltips-popovers', 'back.ui-tooltips-popovers')->name('ui-tooltips-popovers');
    Route::view('/ui-typography', 'back.ui-typography')->name('ui-typography');
    // Spatie : permissions en plus du middleware « admin » (voir RolePermissionSeeder)
    Route::resource('categories', CategoryController::class)->except(['show'])->middleware('permission:gerer-categories');
    Route::patch('/categories/{category}/valider', [CategoryController::class, 'approve'])->middleware('permission:gerer-categories')->name('categories.valider');
    Route::delete('/categories/{category}/refuser', [CategoryController::class, 'reject'])->middleware('permission:gerer-categories')->name('categories.refuser');
    Route::get('/produits', [AdminProductController::class, 'index'])->name('produits.index');
    Route::middleware('permission:valider-produits')->group(function () {
        Route::get('/produits/validation', [AdminProductController::class, 'validation'])->name('produits.validation');
        Route::get('/produits/{product}/revue', [AdminProductController::class, 'revue'])->name('produits.revue');
        Route::post('/produits/{product}/analyser', [AdminProductController::class, 'analyser'])->middleware('throttle:10,1')->name('produits.analyser');
        Route::patch('/produits/{product}/approuver', [AdminProductController::class, 'approuver'])->name('produits.approuver');
        Route::patch('/produits/{product}/refuser', [AdminProductController::class, 'refuser'])->name('produits.refuser');
        Route::patch('/produits/{product}/modification', [AdminProductController::class, 'demanderModification'])->name('produits.modification');
        Route::patch('/produits/{product}/statut', [AdminProductController::class, 'updateStatus'])->name('produits.statut');
    });
    Route::get('/commandes', [AdminCommandeController::class, 'index'])->name('commandes.index');
    Route::get('/commandes/{commande}', [AdminCommandeController::class, 'show'])->name('commandes.show');
    Route::patch('/commandes/{commande}/confirmer', [AdminCommandeController::class, 'confirmer'])->name('commandes.confirmer');
    Route::patch('/commandes/{commande}/refuser', [AdminCommandeController::class, 'refuser'])->name('commandes.refuser');
    Route::patch('/commandes/{commande}/annuler', [AdminCommandeController::class, 'annuler'])->name('commandes.annuler');
    Route::get('/livraisons', [AdminLivraisonController::class, 'index'])->name('livraisons.index');
    Route::get('/livraisons/{livraison}', [AdminLivraisonController::class, 'show'])->name('livraisons.show');
    Route::patch('/livraisons/{livraison}/affecter', [AdminLivraisonController::class, 'affecter'])->name('livraisons.affecter');

    // Module Environnement (Membre 4) - CRUD & IA
    Route::resource('facteurs-emission', FacteurEmissionController::class)->except(['show'])->parameters([
        'facteurs-emission' => 'facteurEmission',
    ]);
    Route::post('bilans-environnementaux/estimer-ia', [BilanEnvironnementalController::class, 'estimerEcoScoreIA'])->name('bilans-environnementaux.estimer-ia');
    Route::resource('bilans-environnementaux', BilanEnvironnementalController::class)->parameters([
        'bilans-environnementaux' => 'bilan',
    ]);

    // Module Réclamations & Avis : traitement, copilote IA, alertes qualité, modération
    Route::middleware('permission:traiter-reclamations')->group(function () {
        Route::resource('reclamations', BackReclamationController::class);
        Route::patch('reclamations/{reclamation}/statut', [BackReclamationController::class, 'updateStatus'])->name('reclamations.statut');
        Route::post('reclamations/{reclamation}/reponses', [ReponseReclamationController::class, 'store'])->name('reclamations.reponses.store');
        Route::post('reclamations/{reclamation}/copilote', [BackReclamationController::class, 'copilote'])->middleware('throttle:15,1')->name('reclamations.copilote');
        Route::post('reclamations/{reclamation}/responsabilite', [BackReclamationController::class, 'responsabilite'])->middleware('throttle:15,1')->name('reclamations.responsabilite');
        Route::post('reclamations/{reclamation}/reanalyser', [BackReclamationController::class, 'reanalyser'])->middleware('throttle:15,1')->name('reclamations.reanalyser');
        Route::get('reclamations/{reclamation}/pdf', [BackReclamationController::class, 'pdf'])->name('reclamations.pdf');
        Route::put('reponses-reclamations/{reponse}', [ReponseReclamationController::class, 'update'])->name('reponses-reclamations.update');
        Route::delete('reponses-reclamations/{reponse}', [ReponseReclamationController::class, 'destroy'])->name('reponses-reclamations.destroy');

        Route::get('alertes-qualite', [AlerteQualiteController::class, 'index'])->name('alertes.index');
        Route::post('alertes-qualite/{alerte}/analyser', [AlerteQualiteController::class, 'analyser'])->middleware('throttle:15,1')->name('alertes.analyser');
        Route::post('alertes-qualite/{alerte}/rappel', [AlerteQualiteController::class, 'rappel'])->name('alertes.rappel');
        Route::patch('alertes-qualite/{alerte}/classer', [AlerteQualiteController::class, 'classer'])->name('alertes.classer');
    });

    Route::middleware('permission:moderer-avis')->group(function () {
        Route::get('avis', [BackAvisController::class, 'index'])->name('avis.index');
        Route::patch('avis/{avis}/publier', [BackAvisController::class, 'publier'])->name('avis.publier');
        Route::patch('avis/{avis}/masquer', [BackAvisController::class, 'masquer'])->name('avis.masquer');
        Route::delete('avis/{avis}', [BackAvisController::class, 'destroy'])->name('avis.destroy');
    });
});
