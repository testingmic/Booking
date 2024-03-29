<?php 
$page_title = "Tickets";

require "headtags.php";
?>
<main>
    <div class="page-header pb-10 page-header-dark bg-gradient-primary-to-secondary">
        <div class="container-fluid">
            <div class="page-header-content">
                <h1 class="page-header-title">
                    <div class="page-header-icon"><i data-feather="award"></i></div>
                    <span><?= $page_title ?></span>
                </h1>
                <ol class="breadcrumb mt-4 mb-0">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active"><?= $page_title ?></li>
                </ol>
            </div>
        </div>
    </div>
    <div class="container-fluid mt-n10">
        <div class="card">
            <div class="card-header">
                <div class="row" style="width:100%">
                    <div class="col-lg-8 col-md-8">
                        Tickets that have generated for Events
                    </div>
                    <?php if($accessObject->hasAccess("generate", "tickets")) { ?>
                    <div class="col-lg-4 col-md-4 text-right pr-0 mr-0">
                        <a href="<?= $baseUrl ?>tickets-generate" class="btn btn-sm btn-outline-primary"><i class="fa fa-plus"></i>&nbsp;Generate</a>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <div class="card-body">
            <?php if(!$accessObject->hasAccess("list", "tickets")) { ?>
                <?= pageNotFound($baseUrl) ?>
            <?php } else { ?>
                <?= form_loader() ?>
                <div class="datatable table-responsive">
                    <table class="table table-hover ticketsList" data-toggle="datatable">
                        <thead>
                            <th width="6%">&#8470;</th>
                            <th>Ticket Title</th>
                            <!-- <th>Event Title</th> -->
                            <th width="15%">&#8470; Generated</th>
                            <th width="10%">&#8470; Sold</th>
                            <th width="10%">&#8470; Left</th>
                            <th width="10%">&#8470; Used</th>
                            <!-- <th>Total Sales</th> -->
                            <th width="12%"></th>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            <?php } ?>
            </div>
        </div>
    </div>

</main>
<?php require "foottags.php"; ?>