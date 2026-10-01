<?php

use Illuminate\Support\Facades\Route;

// ===== Front office (Fruitables) =====
Route::view('/', 'front.home')->name('home');
Route::view('/produits', 'front.produits')->name('produits');
Route::view('/produit-detail', 'front.produit-detail')->name('produit.detail');
Route::view('/panier', 'front.panier')->name('panier');
Route::view('/commande', 'front.commande')->name('commande');
Route::view('/temoignages', 'front.temoignages')->name('temoignages');
Route::view('/contact', 'front.contact')->name('contact');
Route::view('/404', 'errors.404')->name('page404');

// ===== Back office (Sneat) =====
Route::prefix('admin')->name('admin.')->group(function () {
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
});
