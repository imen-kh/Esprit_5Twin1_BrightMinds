<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\EtapeTracabiliteController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\Acteur\CategorySuggestionController;
use App\Http\Controllers\Acteur\EspaceController;
use App\Http\Controllers\Acteur\ProductController as ActeurProductController;
use App\Http\Controllers\PanierController;
use App\Http\Controllers\CommandeController;
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
use App\Http\Controllers\Acteur\ProductionForecastController;
use App\Http\Controllers\PublicLabelController;
use App\Http\Controllers\Back\FacteurEmissionController;
use App\Http\Controllers\Back\BilanEnvironnementalController;
use App\Http\Controllers\Front\EnvironnementController;
use App\Http\Controllers\Front\ReclamationController as FrontReclamationController;
use App\Http\Controllers\Back\ReclamationController as BackReclamationController;
use App\Http\Controllers\ReponseReclamationController;
use Illuminate\Support\Facades\Route;

// ===== Front office (Fruitables) =====
Route::get('/', [CatalogueController::class, 'home'])->name('home');
Route::middleware('compte')->group(function () {
    Route::get('/produits', [CatalogueController::class, 'index'])->name('produits');
    Route::get('/produits/suggestions', [CatalogueController::class, 'suggest'])->name('produits.suggestions');
    Route::get('/produits/{product}', [CatalogueController::class, 'show'])->name('produit.detail');
    // Module Commandes : panier et passage de commande
    Route::get('/panier', [PanierController::class, 'index'])->name('panier');
    Route::get('/panier/intelligent/analyser', fn () => redirect()->route('panier'));
    Route::post('/panier/intelligent/analyser', [PanierController::class, 'smartAnalyze'])->name('panier.smart.analyze');
    Route::get('/panier/intelligent/comprendre', fn () => redirect()->route('panier'));
    Route::post('/panier/intelligent/comprendre', [PanierController::class, 'parseSmartRequest'])->name('panier.smart.parse');
    Route::get('/panier/intelligent/confirmer', fn () => redirect()->route('panier'));
    Route::post('/panier/intelligent/confirmer', [PanierController::class, 'confirmSmart'])->name('panier.smart.confirm');
    Route::post('/panier/optimiser', [PanierController::class, 'optimize'])->name('panier.optimize');
    Route::post('/panier/optimiser/confirmer', [PanierController::class, 'confirmOptimization'])->name('panier.optimize.confirm');
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
// Module Environnement — front-office
// Les segments fixes (simuler, create) sont déclarés avant /{bilan}.
Route::get('/environnement', [EnvironnementController::class, 'index'])->name('front.environnement.index');
Route::post('/environnement/simuler', [EnvironnementController::class, 'simuler'])->name('front.environnement.simuler');

Route::middleware(['auth', 'admin'])->group(function () {
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

    // Mot de passe oublié : demande du lien par email, puis choix du nouveau mot de passe
    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'reset'])->name('password.update');

    // Double authentification : saisie du code reçu par email, après le mot de passe
    Route::get('/verification-connexion', [TwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
    Route::post('/verification-connexion', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('/verification-connexion/renvoyer', [TwoFactorController::class, 'resend'])->name('two-factor.resend');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ===== Mon profil (tous les rôles connectés) =====
Route::middleware('auth')->group(function () {
    Route::resource('reclamations', FrontReclamationController::class);
    Route::post('reclamations/{reclamation}/reponses', [ReponseReclamationController::class, 'store'])->name('reclamations.reponses.store');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::put('/profil/mot-de-passe', [ProfileController::class, 'updatePassword'])->name('profil.password');
    Route::put('/profil/double-authentification', [ProfileController::class, 'toggleTwoFactor'])->name('profil.two-factor');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profil.destroy');
    Route::get('/mes-commandes', [CommandeController::class, 'index'])->name('commandes.index');
    Route::get('/mes-commandes/create', [CommandeController::class, 'create'])->name('commandes.create');
    Route::get('/mes-commandes/{commande}', [CommandeController::class, 'show'])->name('commandes.show');
});

Route::middleware(['auth', 'acteur'])->prefix('acteur')->name('acteur.')->group(function () {
    Route::get('/transformation', [EspaceController::class, 'transformation'])->name('transformation');
    Route::get('/distribution', [EspaceController::class, 'distribution'])->name('distribution');
    Route::get('/production-planner', [ProductionForecastController::class, 'index'])->name('production-planner');
    Route::post('/production-planner/prevoir', [ProductionForecastController::class, 'predict'])->name('production-planner.predict');
    Route::post('/categories/suggerer', [CategorySuggestionController::class, 'store'])->name('categories.suggerer');
    Route::resource('produits', ActeurProductController::class)->parameters(['produits' => 'product'])->except(['show']);
});

// ===== Module Traçabilité : gestion des lots et de leurs étapes (acteurs et administrateurs) =====
Route::middleware(['auth', 'acteur'])->prefix('gestion')->name('gestion.')->group(function () {
    Route::resource('lots', LotController::class);
    Route::resource('etapes', EtapeTracabiliteController::class);
});

// ===== Labels et certifications =====
Route::get('/labels', [PublicLabelController::class, 'index'])->name('labels.index');
Route::get('/labels/{label}', [PublicLabelController::class, 'show'])->name('labels.show');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('labels/create', [AdminLabelController::class, 'create'])->name('labels.create');
    Route::get('labels/{label}/edit', [AdminLabelController::class, 'edit'])->name('labels.edit');
    Route::apiResource('labels', AdminLabelController::class)->except(['create', 'edit']);
    Route::get('certifications/create', [AdminCertificationController::class, 'create'])->name('certifications.create');
    Route::post('certifications', [AdminCertificationController::class, 'store'])->name('certifications.store');
    Route::get('certifications', [AdminCertificationController::class, 'index'])->name('certifications.index');
    Route::get('certifications/{certification}', [AdminCertificationController::class, 'show'])->name('certifications.show');
    Route::get('certifications-expiring', [AdminCertificationController::class, 'expiring'])->name('certifications.expiring');
    Route::patch('certifications/{certification}/validate', [AdminCertificationController::class, 'validate'])->name('certifications.validate');
    Route::delete('certifications/{certification}', [AdminCertificationController::class, 'destroy'])->name('certifications.destroy');
});

Route::prefix('acteur')->name('acteur.')->middleware(['auth', 'acteur'])->group(function () {
    Route::get('certifications/create', [ActeurCertificationController::class, 'create'])->name('certifications.create');
    Route::get('certifications-expiring', [ActeurCertificationController::class, 'expiring'])->name('certifications.expiring');
    Route::apiResource('certifications', ActeurCertificationController::class)->except(['create', 'edit']);
    Route::get('certifications/{certification}/edit', [ActeurCertificationController::class, 'edit'])->name('certifications.edit');
});

// ===== Module Commandes : espace du distributeur (commandes et livraisons) =====
Route::middleware(['auth', 'distributeur'])->prefix('distributeur')->name('distributeur.')->group(function () {
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
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
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
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::patch('/categories/{category}/valider', [CategoryController::class, 'approve'])->name('categories.valider');
    Route::delete('/categories/{category}/refuser', [CategoryController::class, 'reject'])->name('categories.refuser');
    Route::get('/produits', [AdminProductController::class, 'index'])->name('produits.index');
    Route::patch('/produits/{product}/statut', [AdminProductController::class, 'updateStatus'])->name('produits.statut');
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

    Route::resource('reclamations', BackReclamationController::class);
    Route::patch('reclamations/{reclamation}/statut', [BackReclamationController::class, 'updateStatus'])->name('reclamations.statut');
    Route::post('reclamations/{reclamation}/reponses', [ReponseReclamationController::class, 'store'])->name('reclamations.reponses.store');
    Route::put('reponses-reclamations/{reponse}', [ReponseReclamationController::class, 'update'])->name('reponses-reclamations.update');
    Route::delete('reponses-reclamations/{reponse}', [ReponseReclamationController::class, 'destroy'])->name('reponses-reclamations.destroy');
});
