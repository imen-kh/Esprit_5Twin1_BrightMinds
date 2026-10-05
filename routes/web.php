<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\Acteur\CategorySuggestionController;
use App\Http\Controllers\Acteur\ProductController as ActeurProductController;
use App\Http\Controllers\PanierController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CommandeController as AdminCommandeController;
use App\Http\Controllers\Admin\LivraisonController as AdminLivraisonController;
use App\Http\Controllers\Distributeur\CommandeController as DistributeurCommandeController;
use App\Http\Controllers\Distributeur\LivraisonController as DistributeurLivraisonController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

// ===== Front office (Fruitables) =====
Route::get('/', [CatalogueController::class, 'home'])->name('home');
Route::get('/produits', [CatalogueController::class, 'index'])->name('produits');
Route::get('/produits/suggestions', [CatalogueController::class, 'suggest'])->name('produits.suggestions');
Route::get('/produits/{product}', [CatalogueController::class, 'show'])->name('produit.detail');
Route::get('/panier', [PanierController::class, 'index'])->name('panier');
Route::post('/panier/add', [PanierController::class, 'add'])->name('panier.add');
Route::put('/panier/{product_id}', [PanierController::class, 'update'])->name('panier.update');
Route::delete('/panier/{product_id}', [PanierController::class, 'remove'])->name('panier.remove');
Route::get('/commande', [CommandeController::class, 'create'])->middleware('auth')->name('commande');
Route::post('/commande', [CommandeController::class, 'store'])->middleware('auth')->name('commande.store');
Route::view('/temoignages', 'front.temoignages')->name('temoignages');
Route::view('/contact', 'front.contact')->name('contact');
Route::view('/404', 'errors.404')->name('page404');

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
    Route::post('/categories/suggerer', [CategorySuggestionController::class, 'store'])->name('categories.suggerer');
    Route::resource('produits', ActeurProductController::class)->parameters(['produits' => 'product'])->except(['show']);
});

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
    Route::view('/', 'back.dashboard')->name('dashboard');
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
});
