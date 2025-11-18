<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<form id="ldg-demo-form" class="ldg-demo-form">
    <p><label><?php esc_html_e( 'Name', 'live-demo-generator' ); ?></label><br/><input name="name" required /></p>
    <p><label><?php esc_html_e( 'Email', 'live-demo-generator' ); ?></label><br/><input name="email" type="email" required /></p>
    <p><label><?php esc_html_e( 'Role', 'live-demo-generator' ); ?></label><br/>
        <select name="role">
            <option value="administrator"><?php esc_html_e( 'Admin', 'live-demo-generator' ); ?></option>
            <option value="organizer"><?php esc_html_e( 'Organizer', 'live-demo-generator' ); ?></option>
            <option value="attendee"><?php esc_html_e( 'Attendee', 'live-demo-generator' ); ?></option>
        </select>
    </p>
    <p><label><?php esc_html_e( 'Select add-ons to demo', 'live-demo-generator' ); ?></label><br/>
    <?php foreach ( $dep_map_local as $slug => $deps ) : $label = ucwords( str_replace( array( '-', '_' ), ' ', $slug ) ); ?>
        <label style="display:block; margin:2px 0;"><input type="checkbox" name="plugins[]" value="<?php echo esc_attr( $slug ); ?>"/> <?php echo esc_html( $label ); ?></label>
    <?php endforeach; ?>
    </p>
    <p><label><?php esc_html_e( 'Duration', 'live-demo-generator' ); ?></label><br/>
        <select name="duration"><option value="3600">1 hour</option><option value="10800" selected>3 hours</option><option value="86400">24 hours</option></select>
    </p>
    <p><button type="button" class="button button-primary" id="ldg-submit"><?php esc_html_e( 'Create Demo', 'live-demo-generator' ); ?></button></p>
    <div id="ldg-result" style="margin-top:12px"></div>
</form>
