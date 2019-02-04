<?php
global $formation;

$formation_url = get_the_permalink( $formation->ID );
?>
<div class="col-md-11">
    <div class="card ibox mb-4">
        <div class="card-body">
        <h4 class="card-title">
            <a href="<?= $formation_url ?>" class="text-black font-bold"><?= $formation->title ?></a>
        </h4>
        <div class="card-description">

            <div class="row">
            <div class="col-8 uk-padding-remove-right">
                <table class="table">
                    <tbody>
                        <tr>
                            <td>Durée:</td>
                            <td>
                                <?= $formation->duration ?>
                            </td>
                            <td class="font-bold pl-4">Region:</td>
                            <td>
                                <?= $formation->region[0]->name ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-4 uk-flex">
                <div class="uk-flex uk-margin-auto-left">
                <a href="<?= $formation_url ?>" class=" uk-margin-auto-vertical">
                    <button class="btn btn-warning btn-fix">
                    <span class="btn-icon">Voir <i class="la la-plus"></i></span>
                    </button>
                </a>
                </div>
            </div>
            </div>

        </div>
        </div>

    </div>
</div>