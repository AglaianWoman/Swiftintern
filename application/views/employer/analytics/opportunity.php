<?php require_once $dir_employer.'requires/header.php';?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Opportunity Analytics <small>(Internship, Competition, etc)</small></h1>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <form class="form-inline" role="form" id="opportunity_analytics_form">
                <div class="form-group">
                    <div class="input-group">
                        <label class="sr-only">Opportunity</label>
                        <select name="opportunity_id" class="form-control">
                        <?php
                            foreach ($opportunities as $opportunity) {
                                $opportunity = Opportunity::find_by_id('id', $opportunity->id);
                                echo '<option value="'.$opportunity->id.'">'.$opportunity->title.'</option>';
                            }
                        ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <label class="sr-only">Duration</label>
                        <span class="add-on input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                        <input type="text" style="width: 200px" name="range" id="range" class="form-control" value="<?php echo $range; ?>" />
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">View Analytics</button>
            </form>
            <hr>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-bullhorn fa-fw"></i> Reach
                </div>
                <div class="panel-body">
                    <div id="opportunity_reach">Select an opportunity and date range and then click on view analytics button to show details.</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once $dir_employer.'requires/footer.php';?>