<select class="form-control <?php echo e($class); ?>" style="width: 100%;" name="<?php echo e($name); ?>">
    <?php foreach($options as $select => $option): ?>
        <option value="<?php echo e($select); ?>" <?php echo e((string)$select === request($name, $value) ?'selected':''); ?>><?php echo e($option); ?></option>
    <?php endforeach; ?>
</select>