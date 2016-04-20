<?php



/**
 * Roundcube plugin: Automatic addressbook NG
 *
 *
 * @author Bostjan Skufca <bostjan@teon.si>
 * @website https://github.com/teonsystems/roundcube-plugin-automatic-addressbook-ng
 * @licence http://www.gnu.org/licenses/gpl-3.0.html GNU GPLv3+
 */
class automatic_addressbook_ng extends rcube_plugin
{



    /*
     * Addressbook to use by default
     */
    protected $use_addressbook = 'sql';



    /*
     * Group to assign collected addresses to, by default.
     * May be NULL or "" (empty) if adding to group is not desired.
     */
    protected $use_group = 'Collected addresses';



    /*
     * Object and value cache
     */
    protected $rcmail         = null;
    protected $addressbook    = null;
    protected $addressbook_id = null;
    protected $group          = null;
    protected $group_id       = null;



    /**
     * Initialize plugin
     */
    public function init()
    {
        // Configuration is done using main RC config files

        // Add hooks
        $this->add_hook('message_sent', array($this, 'register_recipients'));

        // Store rcmail object instance
        $this->rcmail = rcmail::get_instance();

        // Initiate configuration
        $config = $this->rcmail->config;
        $this->use_addressbook = $config->get('automatic_addressbook_ng_use_addressbook', $this->use_addressbook);
        $this->use_group       = $config->get('automatic_addressbook_ng_use_group',       $this->use_group);
    }



    /**
     * Collect the email address of a just-sent email recipients into
     * the automatic addressbook (if it's not already in another
     * addressbook).
     *
     * @param array $p Hash array containing header and body of sent mail
     * @return nothing
     */
    public function register_recipients($p)
    {
        $rcmail = rcmail::get_instance();

        $headers = $p['headers'];

        // Get all recipients
        $all_recipients = array_merge(
            rcube_mime::decode_address_list($headers['To'], null, true, $headers['charset']),
            rcube_mime::decode_address_list($headers['Cc'], null, true, $headers['charset']),
            rcube_mime::decode_address_list($headers['Bcc'], null, true, $headers['charset'])
        );

        // Get addressbooks
        $abooks = $rcmail->get_address_sources();

        // Loop through all recipients
        foreach ($all_recipients as $recipient) {

            // Skip intances where empty
            if (empty($recipient['mailto'])) {
                continue;
            }

            // Generate basic contact data array
            $contact_data = array(
                'name'  => $recipient['name'],
                'email' => $recipient['mailto'],
            );

            // If name is not explicitly given, use left part of email address as name
            if (empty($contact_data['name']) || $contact_data['name'] == $contact_data['email']) {
                $contact_data['name'] = ucfirst(preg_replace(
                    '/[\.\-]/',
                    ' ',
                    substr($contact_data['email'], 0, strpos($contact_data['email'], '@'))
                ));
            }

            // If previous entries indicate this address is already in addressbook, skip
            foreach ($abooks as $abook_data) {
                $abook            = $this->rcmail->get_address_book($abook_data['id']);
                $previous_entries = $abook->search('email', $contact_data['email'], false, false);
                if ($previous_entries->count > 0) {
                    continue 2;
                }
            }

            // If we reach this point, this address is not in any of address books, so let's save it
            $abook      = $this->get_addressbook();
            $contact_id = $abook->insert($contact_data);

            // If adding to group is not required, skip to next email
            if (empty($this->use_group)) {
                continue;
            }

            $group_id = $this->get_group_id();
            $abook->add_to_group($group_id, $contact_id);
        }
    }



    /**
     * Get addressbook
     *
     * @return   Object   Writeable addressbook object
     */
    protected function get_addressbook ()
    {
        if (NULL === $this->addressbook) {
            $this->addressbook    = $this->rcmail->get_address_book($this->use_addressbook, true);
            $this->addressbook_id = $this->rcmail->get_address_book_id($this->addressbook);
        }

        return $this->addressbook;
    }



    /**
     * Get addressbook ID
     *
     * @return   ?   Writeable addressbook ID
     */
    protected function get_addressbook_id ()
    {
        if (NULL === $this->addressbook_id) {
            $this->get_addressbook();
        }

        return $this->addressbook_id;
    }



    /**
     * Get group I
     *
     * Finds or creates group and returns its group I
     *
     * @return   ?   Group id
     */
    protected function get_group_id ()
    {
        if (NULL === $this->group_id) {
            $abook         = $this->get_addressbook();
            $search_result = $abook->list_groups($this->use_group, 1);

            if (0 == count($search_result)) {
                $create_result = $abook->create_group($this->use_group);
                $this->group_id = $create_result['id'];
            }
            else {
                $this->group_id = $search_result[0]['ID'];
            }
        }

        return $this->group_id;
    }
}
