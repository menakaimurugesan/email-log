<?php
/**
Plugin Name: Email Log
Plugin URI: http://sudarmuthu.com/wordpress/email-log
Description: Logs every email sent through WordPress. Compatiable with WPMU too.
Author: Sudar
Version: 0.4
Author URI: http://sudarmuthu.com/
Text Domain: email-log

=== RELEASE NOTES ===
2009-10-08 - v0.1 - Initial Release
2009-10-15 - v0.2 - Added compatability for MySQL 4
2009-10-19 - v0.3 - Added compatability for MySQL 4 (Thanks Frank)
2010-01-02 - v0.4 - Added german translation (Thanks Frank)
*/

global $wpdb;
global $smel_table_name;
$smel_table_name = $wpdb->prefix . "email_log";

// TODO - Should find some way to get away with these global variables.
global $smel_db_version;
$smel_db_version = "0.1";

class EmailLog {

    private $table_name ;    /* Database table name */
    private $db_version ;    /* Database version */

    /**
     * Initalize the plugin by registering the hooks
     */
    function __construct() {

        global $wpdb;
        global $smel_table_name;
        global $smel_db_version;

        // Load localization domain
        load_plugin_textdomain( 'email-log', false, dirname(plugin_basename(__FILE__)) . '/languages' );

        // Register hooks
        add_action( 'admin_menu', array(&$this, 'register_settings_page') );

        // Register Filter
        add_filter('wp_mail', array(&$this, 'log_email'));

        $plugin = plugin_basename(__FILE__);
        add_filter("plugin_action_links_$plugin", array(&$this, 'add_action_links'));

        // Initialize Variables
        $this->table_name = $smel_table_name;
        $this->db_version = $smel_db_version;
    }

    /**
     * Register the settings page
     */
    function register_settings_page() {
        add_options_page( __('Email Log', 'email-log'), __('Email Log', 'email-log'), 8, 'email-log', array(&$this, 'settings_page') );
    }

    /**
     * hook to add action links
     *
     * @param <type> $links
     * @return <type>
     */
    function add_action_links( $links ) {
        // Add a link to this plugin's settings page
        $settings_link = '<a href="options-general.php?page=email-log">' . __("Settings", 'email-log') . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Adds Footer links. Based on http://striderweb.com/nerdaphernalia/2008/06/give-your-wordpress-plugin-credit/
     */
    function add_footer_links() {
        $plugin_data = get_plugin_data( __FILE__ );
        printf('%1$s ' . __("plugin", 'email-log') .' | ' . __("Version", 'email-log') . ' %2$s | '. __('by', 'email-log') . ' %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
    }

    /**
     * Dipslay the Settings page
     *
     * Some parts of this function is based on the wp-rattings Plugin http://wordpress.org/extend/plugins/email-log/
     */
    function settings_page() {
        global $wpdb;
        global $text_direction;

        $base_name = plugin_basename('email-log');
        $base_page = 'admin.php?page='.$base_name;

        $email_log_page = intval($_GET['emaillog_page']);
        $emaillogs_filterid = trim(addslashes($_GET['id']));
        $emaillogs_filter_to_email = trim(addslashes($_GET['to_email']));
        $emaillogs_filter_subject = trim(addslashes($_GET['subject']));
        $emaillog_sort_by = trim($_GET['by']);
        $emaillog_sortby_text = '';
        $emaillog_sortorder = trim($_GET['order']);
        $emaillog_sortorder_text = '';
        $email_log_perpage = intval($_GET['perpage']);
        $emaillog_sort_url = '';

        ### Form Processing
        if(!empty($_POST['do'])) {
            // Decide What To Do
            switch($_POST['do']) {
                case __('Delete Logs', 'email-log'):
                    $delete_datalog = intval($_POST['delete_datalog']);
                    switch($delete_datalog) {
                        case 1:
                            // Delete based on condition
                            $to_email = trim(addslashes( $_POST['delete_to_email']));
                            if ('' != $to_email) {
                                $delete_logs = $wpdb->query("DELETE FROM $this->table_name where to_email = '$to_email'");
                                if($delete_logs) {
                                    $text = '<font color="green">'.sprintf(__('All Email Logs For email id %s Have Been Deleted.', 'email-log'), $to_email).'</font>';
                                } else {
                                    $text = '<font color="red">'.sprintf(__('An Error Has Occured while deleting All Email Logs For email id %s.', 'email-log'), $to_email).'</font>';
                                }
                            }

                            $subject = trim(addslashes( $_POST['delete_subject']));
                            if ('' != $subject) {
                                $delete_logs = $wpdb->query("DELETE FROM $this->table_name where subject = '$subject'");
                                if($delete_logs) {
                                    $text .= '<font color="green">'.sprintf(__('All Email Logs With Subject %s Have Been Deleted.', 'email-log'), $subject).'</font>';
                                } else {
                                    $text .= '<font color="red">'.sprintf(__('An Error Has Occured While Deleting All Email Logs With Subject %s.', 'email-log'), $subject).'</font>';
                                }
                            }
                            break;
                        case 2:
                            // Delete all
                            $delete_logs = $wpdb->query("DELETE FROM $this->table_name ");
                            if ($delete_logs) {
                                $text = '<font color="green">'.__('All Email log Has Been Deleted.', 'email-log').'</font><br />';
                            } else {
                                $text = '<font color="red">'.__('An Error Has Occured While Deleting All Email Logs', 'email-log').'</font>';
                            }
                            break;
                    }
                break;
            }
        }

        ### Form Sorting URL
        if(!empty($emaillogs_filterid)) {
            $emaillogs_filterid = intval($emaillogs_filterid);
            $emaillog_sort_url .= '&amp;id='.$emaillogs_filterid;
        }
        if(!empty($emaillogs_filter_to_email)) {
            $emaillog_sort_url .= '&amp;to_email='.$emaillogs_filter_to_email;
        }
        if(!empty($emaillogs_filter_subject)) {
            $emaillog_sort_url .= '&amp;subject='.$emaillogs_filter_subject;
        }
        if(!empty($emaillog_sort_by)) {
            $emaillog_sort_url .= '&amp;by='.$emaillog_sort_by;
        }
        if(!empty($emaillog_sortorder)) {
            $emaillog_sort_url .= '&amp;order='.$emaillog_sortorder;
        }
        if(!empty($email_log_perpage)) {
            $email_log_perpage = intval($email_log_perpage);
            $emaillog_sort_url .= '&amp;perpage='.$email_log_perpage;
        }

        ### Get Order By
        switch($emaillog_sort_by) {
            case 'id':
                $emaillog_sort_by = 'id';
                $emaillog_sortby_text = __('ID', 'email-log');
                break;
            case 'to_email':
                $emaillog_sort_by = 'to_email';
                $emaillog_sortby_text = __('To Email', 'email-log');
                break;
            case 'subject':
                $emaillog_sort_by = 'subject';
                $emaillog_sortby_text = __('Subject', 'email-log');
                break;
            case 'date':
            default:
                $emaillog_sort_by = 'sent_date';
                $emaillog_sortby_text = __('Date', 'email-log');
        }

        ### Get Sort Order
        switch($emaillog_sortorder) {
            case 'asc':
                $emaillog_sortorder = 'ASC';
                $emaillog_sortorder_text = __('Ascending', 'email-log');
                break;
            case 'desc':
            default:
                $emaillog_sortorder = 'DESC';
                $emaillog_sortorder_text = __('Descending', 'email-log');
        }

        // Where
        $emaillog_where = '';
        if(!empty($emaillogs_filterid)) {
            $emaillog_where = "AND id =$emaillogs_filterid";
        }
        if(!empty($emaillogs_filter_to_email)) {
            $emaillog_where .= " AND to_email like '%$emaillogs_filter_to_email%'";
        }
        if(!empty($emaillogs_filter_subject)) {
            $emaillog_where .= " AND subject like '%$emaillogs_filter_subject%'";
        }

        // Get email Logs Data
        $total_logs = $wpdb->get_var("SELECT COUNT(id) FROM $this->table_name WHERE 1=1 $emaillog_where");

        // Checking $postratings_page and $offset
        if(empty($email_log_page) || $email_log_page == 0) { $email_log_page = 1; }
        if(empty($offset)) { $offset = 0; }
        if(empty($email_log_perpage) || $email_log_perpage == 0) { $email_log_perpage = 20; }

        // Determin $offset
        $offset = ($email_log_page-1) * $email_log_perpage;

        // Determine Max Number Of Logs To Display On Page
        if(($offset + $email_log_perpage) > $total_logs) {
            $max_on_page = $total_logs;
        } else {
            $max_on_page = ($offset + $email_log_perpage);
        }

        // Determine Number Of Logs To Display On Page
        if (($offset + 1) > ($total_logs)) {
            $display_on_page = $total_logs;
        } else {
            $display_on_page = ($offset + 1);
        }

        // Determing Total Amount Of Pages
        $total_pages = ceil($total_logs / $email_log_perpage);

        // Get The Logs
        $email_logs = $wpdb->get_results("SELECT * FROM $this->table_name WHERE 1=1 $emaillog_where ORDER BY $emaillog_sort_by $emaillog_sortorder LIMIT $offset, $email_log_perpage");
?>
        <?php if(!empty($text)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>'; } ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e( 'Email Log Settings', 'email-log' ); ?></h2>
            <p><?php printf(__('Displaying <strong>%s</strong> to <strong>%s</strong> of <strong>%s</strong> Email log entries.', 'email-log'), number_format_i18n($display_on_page), number_format_i18n($max_on_page), number_format_i18n($total_logs)); ?></p>
            <p><?php printf(__('Sorted by <strong>%s</strong> in <strong>%s</strong> order.', 'email-log'), $emaillog_sortby_text, $emaillog_sortorder_text); ?></p>

<?php
			if($total_pages > 1) {
?>
		<br />
		<table class="widefat">
			<tr>
				<td align="<?php echo ('rtl' == $text_direction) ? 'right' : 'left'; ?>" width="40%">
					<?php
						if($email_log_page > 1 && ((($email_log_page*$email_log_perpage)-($email_log_perpage-1)) <= $total_logs)) {
							echo '<strong>&laquo;</strong> <a href="'.$base_page.'&amp;emaillog_page='.($email_log_page-1).$emaillog_sort_url.'" title="&laquo; '.__('Previous Page', 'email-log').'">'.__('Previous Page', 'email-log').'</a>';
						} else {
							echo '&nbsp;';
						}
					?>
				</td>
                <td align="center" width="20%">
					<?php printf(__('Pages (%s): ', 'email-log'), number_format_i18n($total_pages)); ?>
					<?php
						if ($email_log_page >= 4) {
							echo '<strong><a href="'.$base_page.'&amp;emaillog_page=1'.$emaillog_sort_url.$emaillog_sort_url.'" title="'.__('Go to First Page', 'email-log').'">&laquo; '.__('First', 'email-log').'</a></strong> ... ';
						}
						if($email_log_page > 1) {
							echo ' <strong><a href="'.$base_page.'&amp;emaillog_page='.($email_log_page-1).$emaillog_sort_url.'" title="&laquo; '.__('Go to Page', 'email-log').' '.number_format_i18n($email_log_page-1).'">&laquo;</a></strong> ';
						}
						for($i = $email_log_page - 2 ; $i  <= $email_log_page +2; $i++) {
							if ($i >= 1 && $i <= $total_pages) {
								if($i == $email_log_page) {
									echo '<strong>['.number_format_i18n($i).']</strong> ';
								} else {
									echo '<a href="'.$base_page.'&amp;emaillog_page='.($i).$emaillog_sort_url.'" title="'.__('Page', 'email-log').' '.number_format_i18n($i).'">'.number_format_i18n($i).'</a> ';
								}
							}
						}
						if($email_log_page < $total_pages) {
							echo ' <strong><a href="'.$base_page.'&amp;emaillog_page='.($email_log_page+1).$emaillog_sort_url.'" title="'.__('Go to Page', 'email-log').' '.number_format_i18n($email_log_page+1).' &raquo;">&raquo;</a></strong> ';
						}
						if (($email_log_page+2) < $total_pages) {
							echo ' ... <strong><a href="'.$base_page.'&amp;emaillog_page='.($total_pages).$emaillog_sort_url.'" title="'.__('Go to Last Page', 'email-log').'">'.__('Last', 'email-log').' &raquo;</a></strong>';
						}
					?>
				</td>
				<td align="<?php echo ('rtl' == $text_direction) ? 'left' : 'right'; ?>" width="40%">
					<?php
						if($email_log_page >= 1 && ((($email_log_page*$email_log_perpage)+1) <=  $total_logs)) {
							echo '<a href="'.$base_page.'&amp;emaillog_page='.($email_log_page+1).$emaillog_sort_url.'" title="'.__('Next Page', 'email-log').' &raquo;">'.__('Next Page', 'email-log').'</a> <strong>&raquo;</strong>';
						} else {
							echo '&nbsp;';
						}
					?>
				</td>
			</tr>
		</table>
		<!-- </Paging> -->
		<?php
			}
		?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th width="5%"><?php _e('ID', 'email-log'); ?></th>
                        <th width="20%"><?php _e('Date / Time', 'email-log'); ?></th>
                        <th width="30%"><?php _e('To', 'email-log'); ?></th>
                        <th width="45%"><?php _e('Subject', 'email-log'); ?></th>
                    </tr>
                </thead>
                <tbody>
<?php
                if($email_logs) {
                    $i = 0;
                    foreach($email_logs as $email_log) {
                        if($i%2 == 0) {
                            $style = 'class="alternate"';
                        }  else {
                            $style = '';
                        }
                        $email_id = intval($email_log->id);
                        $email_date = mysql2date(sprintf(__('%s @ %s', 'email-log'), get_option('date_format'), get_option('time_format')), $email_log->sent_date);
                        $email_to = stripslashes($email_log->to_email);
                        $email_subject = stripslashes($email_log->subject);
                        echo "<tr $style>\n";
                        echo '<td>'.$email_id.'</td>'."\n";
                        echo "<td>$email_date</td>\n";
                        echo "<td>$email_to</td>\n";
                        echo "<td>$email_subject</td>\n";
                        echo '</tr>';
                        $i++;
                    }
                } else {
                    echo '<tr><td colspan="7" align="center"><strong>'.__('No email Logs Found', 'email-log').'</strong></td></tr>';
                }
?>
                </tbody>
                <tfoot>
                    <tr>
                        <th width="5%"><?php _e('ID', 'email-log'); ?></th>
                        <th width="20%"><?php _e('Date / Time', 'email-log'); ?></th>
                        <th width="30%"><?php _e('To', 'email-log'); ?></th>
                        <th width="45%"><?php _e('Subject', 'email-log'); ?></th>
                    </tr>
                </tfoot>
            </table>
<?php
			if($total_pages > 1) {
?>
		<table class="widefat">
			<tr>
				<td align="<?php echo ('rtl' == $text_direction) ? 'right' : 'left'; ?>" width="40%">
					<?php
						if($email_log_page > 1 && ((($email_log_page*$email_log_perpage)-($email_log_perpage-1)) <= $total_logs)) {
							echo '<strong>&laquo;</strong> <a href="'.$base_page.'&amp;emaillog_page='.($email_log_page-1).$emaillog_sort_url.'" title="&laquo; '.__('Previous Page', 'email-log').'">'.__('Previous Page', 'email-log').'</a>';
						} else {
							echo '&nbsp;';
						}
					?>
				</td>
                <td align="center" width="20%">
					<?php printf(__('Pages (%s): ', 'email-log'), number_format_i18n($total_pages)); ?>
					<?php
						if ($email_log_page >= 4) {
							echo '<strong><a href="'.$base_page.'&amp;emaillog_page=1'.$emaillog_sort_url.$emaillog_sort_url.'" title="'.__('Go to First Page', 'email-log').'">&laquo; '.__('First', 'email-log').'</a></strong> ... ';
						}
						if($email_log_page > 1) {
							echo ' <strong><a href="'.$base_page.'&amp;emaillog_page='.($email_log_page-1).$emaillog_sort_url.'" title="&laquo; '.__('Go to Page', 'email-log').' '.number_format_i18n($email_log_page-1).'">&laquo;</a></strong> ';
						}
						for($i = $email_log_page - 2 ; $i  <= $email_log_page +2; $i++) {
							if ($i >= 1 && $i <= $total_pages) {
								if($i == $email_log_page) {
									echo '<strong>['.number_format_i18n($i).']</strong> ';
								} else {
									echo '<a href="'.$base_page.'&amp;emaillog_page='.($i).$emaillog_sort_url.'" title="'.__('Page', 'email-log').' '.number_format_i18n($i).'">'.number_format_i18n($i).'</a> ';
								}
							}
						}
						if($email_log_page < $total_pages) {
							echo ' <strong><a href="'.$base_page.'&amp;emaillog_page='.($email_log_page+1).$emaillog_sort_url.'" title="'.__('Go to Page', 'email-log').' '.number_format_i18n($email_log_page+1).' &raquo;">&raquo;</a></strong> ';
						}
						if (($email_log_page+2) < $total_pages) {
							echo ' ... <strong><a href="'.$base_page.'&amp;emaillog_page='.($total_pages).$emaillog_sort_url.'" title="'.__('Go to Last Page', 'email-log').'">'.__('Last', 'email-log').' &raquo;</a></strong>';
						}
					?>
				</td>
				<td align="<?php echo ('rtl' == $text_direction) ? 'left' : 'right'; ?>" width="40%">
					<?php
						if($email_log_page >= 1 && ((($email_log_page*$email_log_perpage)+1) <=  $total_logs)) {
							echo '<a href="'.$base_page.'&amp;emaillog_page='.($email_log_page+1).$emaillog_sort_url.'" title="'.__('Next Page', 'email-log').' &raquo;">'.__('Next Page', 'email-log').'</a> <strong>&raquo;</strong>';
						} else {
							echo '&nbsp;';
						}
					?>
				</td>
			</tr>
			<tr class="alternate">
			</tr>
		</table>
		<!-- </Paging> -->
		<?php
			}
		?>
	<br />

    <form action="<?php echo esc_url($_SERVER['PHP_SELF']); ?>" method="get">
		<input type="hidden" name="page" value="<?php echo $base_name; ?>" />
		<table class="widefat">
			<tr>
				<th><?php _e('Filter Options:', 'email-log'); ?></th>
				<td>
					<?php _e('ID:', 'email-log'); ?>&nbsp;<input type="text" name="id" value="<?php echo $emaillogs_filterid; ?>" size="5" maxlength="5" />
					&nbsp;&nbsp;&nbsp;
					<?php _e('To Email:', 'email-log'); ?>&nbsp;<input type="text" name="to_email" value="<?php echo $emaillogs_filter_to_email; ?>" size="40" maxlength="50" />
					&nbsp;&nbsp;&nbsp;
					<?php _e('Subject:', 'email-log'); ?>&nbsp;<input type="text" name="subject" value="<?php echo $emaillogs_filter_subject; ?>" size="40" maxlength="50" />
					&nbsp;&nbsp;&nbsp;
				</td>
			</tr>
			<tr class="alternate">
				<th><?php _e('Sort Options:', 'email-log'); ?></th>
				<td>
					<select name="by" size="1">
						<option value="id"<?php if($emaillog_sort_by == 'id') { echo ' selected="selected"'; }?>><?php _e('ID', 'email-log'); ?></option>
						<option value="to_email"<?php if($emaillog_sort_by == 'to_email') { echo ' selected="selected"'; }?>><?php _e('To Email', 'email-log'); ?></option>
						<option value="subject"<?php if($emaillog_sort_by == 'subject') { echo ' selected="selected"'; }?>><?php _e('Subject', 'email-log'); ?></option>
						<option value="sent_date"<?php if($emaillog_sort_by == 'sent_date') { echo ' selected="selected"'; }?>><?php _e('Date', 'email-log'); ?></option>
					</select>
					&nbsp;&nbsp;&nbsp;
					<select name="order" size="1">
						<option value="asc"<?php if($emaillog_sortorder == 'ASC') { echo ' selected="selected"'; }?>><?php _e('Ascending', 'email-log'); ?></option>
						<option value="desc"<?php if($emaillog_sortorder == 'DESC') { echo ' selected="selected"'; } ?>><?php _e('Descending', 'email-log'); ?></option>
					</select>
					&nbsp;&nbsp;&nbsp;
					<select name="perpage" size="1">
					<?php
						for($i=10; $i <= 100; $i+=10) {
							if($email_log_perpage == $i) {
								echo "<option value=\"$i\" selected=\"selected\">".__('Per Page', 'email-log').": ".number_format_i18n($i)."</option>\n";
							} else {
								echo "<option value=\"$i\">".__('Per Page', 'email-log').": ".number_format_i18n($i)."</option>\n";
							}
						}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center"><input type="submit" value="<?php _e('Go', 'email-log'); ?>" class="button" /></td>
			</tr>
		</table>
	</form>
</div>
<p>&nbsp;</p>
<!-- Delete Email Logs -->
<div class="wrap">
	<h3><?php _e('Delete Logs', 'email-log'); ?></h3>
	<br style="clear" />
	<div align="center">
        <form method="post" action="<?php echo esc_url($_SERVER['PHP_SELF']); ?>?page=<?php echo $base_name; ?>">
		<table class="widefat">
			<tr>
				<td valign="top"><b><?php _e('Delete Type : ', 'email-log'); ?></b></td>
				<td valign="top">
					<select size="1" name="delete_datalog">
						<option value="1"><?php _e('Based on', 'email-log'); ?></option>
						<option value="2"><?php _e('All Logs', 'email-log'); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td valign="top"><b><?php _e('Condition:', 'email-log'); ?></b></td>
				<td valign="top">
                    <label for ="delete_to_email"><?php _e('To Email', 'email-log');?> <input type="text" name="delete_to_email" size="20" dir="ltr" /></label>
                    <?php _e('or', 'email-log');?>
                    <label for ="delete_subject"><?php _e('Subject', 'email-log');?> <input type="text" name="delete_subject" size="20" dir="ltr" /></label>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="do" value="<?php _e('Delete Logs', 'email-log'); ?>" class="button" onclick="return confirm('<?php _e('You Are About To Delete Email Logs.\nThis Action Is Not Reversible.\n\n Choose \\\'Cancel\\\' to stop, \\\'OK\\\' to delete.', 'email-log'); ?>')" />
				</td>
			</tr>
		</table>
		</form>
	</div>
</div>
<?php
        // Display credits in Footer
        add_action( 'in_admin_footer', array(&$this, 'add_footer_links'));
    }

    /**
     * Log all email to database
     *
     * @global object $wpdb
     * @param array $mail_info Information about email
     * @return array Information about email
     */
    function log_email($mail_info) {

        global $wpdb;

        $attachment_present = (count ($mail_info['attachments']) > 0) ? "true" : "false";

        // Log into the database
        $wpdb->insert($this->table_name, array(
                'to_email' => $mail_info['to'],
                'subject' => $mail_info['subject'],
                'message' => $mail_info['message'],
                'headers' => $mail_info['headers'],
                'attachments' => $attachment_present
        ));

        // return unmodifiyed array
        return $mail_info;
    }

    // PHP4 compatibility
    function EmailLog() {
        $this->__construct();
    }
}

/**
 * Create database table when the Plugin is installed for the first time
 *
 * @global object $wpdb
 * @global string $smel_table_name Table Name
 * @global string $smel_db_version DB Version
 */
function smel_on_install() {

   global $wpdb;
   global $smel_table_name;
   global $smel_db_version;

   if($wpdb->get_var("show tables like '{$smel_table_name}'") != $smel_table_name) {

      $sql = "CREATE TABLE " . $smel_table_name . " (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          to_email VARCHAR(100) NOT NULL,
          subject VARCHAR(250) NOT NULL,
          message TEXT NOT NULL,
          headers TEXT NOT NULL,
          attachments TEXT NOT NULL,
          sent_date timestamp(14) NOT NULL,
          PRIMARY KEY  (id)
        );";

      require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
      dbDelta($sql);

      add_option("email-log-db", $smel_db_version);
   }
}

// When installed
register_activation_hook(__FILE__, 'smel_on_install');

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'EmailLog' ); function EmailLog() { global $EmailLog; $EmailLog = new EmailLog(); }

?>