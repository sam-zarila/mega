<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-bold tw-text-xl tw-mb-4"><?= e($title); ?></h4>
                <div class="panel_s">
                    <div class="panel-body">
                        <?php $this->load->view('approvals/partials/requests_table', ['requests' => $requests, 'show_approver' => true]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
