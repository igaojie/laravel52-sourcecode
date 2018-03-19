<table <?php echo $attributes; ?>>
    <thead>
    <tr>
        <?php foreach($headers as $header): ?>
            <th><?php echo e($header); ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach($rows as $row): ?>
    <tr>
        <?php foreach($row as $item): ?>
        <td><?php echo $item; ?></td>
        <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>