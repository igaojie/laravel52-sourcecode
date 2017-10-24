<div <?php echo $attributes; ?>>
    <?php foreach($items as $key => $item): ?>
    <div class="panel box box-primary" style="margin-bottom: 0px">
        <div class="box-header with-border">
            <h4 class="box-title">
                <a data-toggle="collapse" data-parent="#<?php echo e($id); ?>" href="#collapse<?php echo e($key); ?>">
                    <?php echo e($item['title']); ?>

                </a>
            </h4>
        </div>
        <div id="collapse<?php echo e($key); ?>" class="panel-collapse collapse <?php echo e($key == 0 ? 'in' : ''); ?>">
            <div class="box-body">
                <?php echo $item['content']; ?>

            </div>
        </div>
    </div>
    <?php endforeach; ?>

</div>
