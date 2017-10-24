<div class="row">
    <div class="col-md-9">
        <div class="chart-responsive">
            <canvas id="<?php echo e($id); ?>" style="height: 100%; width: 100%;"></canvas>
        </div><!-- ./chart-responsive -->
    </div><!-- /.col -->
    <div class="col-md-3">
        <ul class="chart-legend clearfix">
            <?php foreach($data as $item): ?>
            <li><i class="fa fa-circle-o" style="color: <?php echo e($item['color']); ?> !important;"></i> <?php echo e($item['label']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>