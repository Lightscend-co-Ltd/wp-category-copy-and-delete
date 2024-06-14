<?php
// Get all taxonomies
$taxonomies = get_taxonomies( [], 'objects' );
?>
<h1>タクソノミーコピーと削除</h1>
<form action="" method="post">
    <h2>タクソノミーコピー</h2>
    <label for="source_taxonomy">コピー元タクソノミー:</label>
    <select id="source_taxonomy" name="source_taxonomy">
        <?php foreach ( $taxonomies as $taxonomy ) : ?>
            <option value="<?php echo esc_attr( $taxonomy->name ); ?>"><?php echo esc_html( $taxonomy->labels->name ); ?></option>
        <?php endforeach; ?>
    </select>
    <label for="destination_taxonomy">コピー先タクソノミー:</label>
    <select id="destination_taxonomy" name="destination_taxonomy">
        <?php foreach ( $taxonomies as $taxonomy ) : ?>
            <option value="<?php echo esc_attr( $taxonomy->name ); ?>"><?php echo esc_html( $taxonomy->labels->name ); ?></option>
        <?php endforeach; ?>
    </select>
    <input type="submit" value="コピー開始" name="copy_taxonomy">

    <h2>タクソノミー削除</h2>
    <label for="taxonomy_to_remove">削除するタクソノミー:</label>
    <select id="taxonomy_to_remove" name="taxonomy_to_remove">
        <?php foreach ( $taxonomies as $taxonomy ) : ?>
            <option value="<?php echo esc_attr( $taxonomy->name ); ?>"><?php echo esc_html( $taxonomy->labels->name ); ?></option>
        <?php endforeach; ?>
    </select>
    <input type="submit" value="削除開始" name="delete_taxonomy">
</form>