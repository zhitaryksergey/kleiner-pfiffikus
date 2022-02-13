<?php

/**
 * Fired during plugin activation
 *
 * @link       http://weslink.de
 * @since      1.0.0
 *
 * @package    Weslink_Payjoe_Opbeleg
 * @subpackage Weslink_Payjoe_Opbeleg/includes
 * @author     Weslink <kontakt@weslink.de>
 */

require_once plugin_dir_path(__FILE__) . '/../helpers/helpers.php';
require_once plugin_dir_path(__FILE__) . '/partials/constants.php';

class Weslink_Payjoe_Opbeleg_Orders
{
    /**
     * @var array
     */
    private $orders_to_exclude;

    public function __construct()
    {
        $this->orders_to_exclude = [];
    }

    /**
     * Returns order details
     */
    public function getOrders($log_json_data = false)
    {
        $transfer_count = (int)get_option('payjoe_transfer_count', 10);
        $start_order_date = get_option('payjoe_start_order_date');

        list($invoice_number_field_key, $invoice_date_field_key) = $this->getInvoiceCustomFieldKeys();

        $args = array(
            'post_type' => wc_get_order_types(),
            'posts_per_page' => $transfer_count,
            'post_status' => array_keys(wc_get_order_statuses()),
            'meta_key' => '_payjoe_status',
            // Prefer orders with a low numeric _payjoe_status to avoid
            // old orders with errors blocking the upload of newer orders.
            // Then make sure older orders are uploaded first.
            'orderby' => array(
                'meta_value_num' => 'ASC',
                'ID'      => 'ASC',
            ),
            'meta_query' => array(
                'relation' => 'AND'
                /*
                ,
                array(
                    'key' => $invoice_number_field_key,
                    'value' => (int)get_option('payjoe_startrenr'),
                    'type' => 'NUMERIC',
                    'compare' => '>='
                )
                */
                ,
                array(
                    'key' => $invoice_number_field_key,
                    'compare' => 'EXISTS'
                )
                ,
                array(
                    'relation' => 'OR'
                    ,
                    array(
                        'key' => '_payjoe_status',
                        'value' => (int)PAYJOE_STATUS_OK,
                        'type' => 'NUMERIC',
                        'compare' => '!=' // already submitted to API but error
                    )
                    ,
                    array(
                        'key' => '_payjoe_status',
                        'compare' => 'NOT EXISTS', // not submitted to API yet
                    )
                )
            )
        );

        if ($start_order_date) {
            $args['date_query'] = [
                "after" => $start_order_date,
                "inclusive" => true
            ];
        }

        // Germanized
        if (get_option('payjoe_invoice_options') === '1') {
            // Not sure if it applies to the others as well.
            $args['parent'] = 0;
        }

        $orders = new WP_Query($args);

        $numOrders = $orders->post_count;
        echo("$numOrders orders selected for upload.\n");

        if ($orders->have_posts()) {
            /**
             * This will be returned data
             */
            $wlOrders = array();
            /**
             * Loop over each order
             */
            while ($orders->have_posts()) {
                $orders->the_post();

                $invoice_number = get_post_meta(get_the_ID(), $invoice_number_field_key, true);
                $invoice_date = get_post_meta(get_the_ID(), $invoice_date_field_key, true);
                $invoices = get_post_meta(get_the_ID(), '_payjoe_invoices', true);

                // Will not be set for the non-germanized invoicing plugins and for order that
                // were not yet updated. Fill it with default values.
                if (!$invoices) {
                    if (!$invoice_number || $invoice_number < 0) {
                        // No useful data, should be ignored in the next upload
                        delete_post_meta(get_the_ID(), $invoice_number_field_key);
                        continue;
                    }
                    $invoices = [[
                        'number' => $invoice_number,
                        'date' => $invoice_date,
                        // Refunds were not handled by the old code
                        'type' => 0,
                    ]];
                }

                $order_details = new WC_Order(get_the_ID());

                foreach($invoices as $invoice_data) {
                    $order_id = $order_details->get_order_number();
                    $invoice_number = $invoice_data['number'];
                    $invoice_date = $invoice_data['date'];
                    $invoice_type = $invoice_data['type'];
                    echo "Will upload order $order_id with invoice $invoice_number (type $invoice_type, $invoice_date) \n";

                    $obj_op_auftragsposten = $this->getOpData($order_details, $invoice_data);
                    $wlOrders[] = $obj_op_auftragsposten;
                }
            }
            wp_reset_postdata();

            // API limit
            $chunk_size = min(500, $transfer_count);
            $wlOrders_chunks = array_chunk($wlOrders, $chunk_size);

            $theOrders = [];

            foreach ($wlOrders_chunks as $aChunk) {
                $theOrders[] = array(
                    'UserName' => get_option('payjoe_username'),
                    'APIKey' => get_option('payjoe_apikey'),
                    'OPBelegZugangID' => (int)get_option('payjoe_zugangsid'),
                    'OPAuftragsposten' => $aChunk
                );
            }

            foreach ($theOrders as $aOrder) {
                //send order to payJoe
                $result = $this->uploadBelegtoPayJoe($aOrder);

                if ($log_json_data) {
                    echo "\n ----------------------------  NEXT ORDER ----------------------------\n";
                    echo "\n ---------------------------- API RESULT----------------------------\n";
                    echo $result;
                    echo "\n ---------------------------- JSON DATA ----------------------------\n";
                    $json_result = json_encode($aOrder);
                    echo($json_result === FALSE ? json_last_error_msg() : $json_result);
                    echo "\n --------------------------------------------------------------------\n";
                    echo "\n -------------------------- Data array view -------------------------\n";
                    print_r($aOrder);
                    print_r(json_decode($result));
                    echo "\n --------------------------------------------------------------------\n";
                }

                if ($result) {
                    $this->handleAPIResult($result, $aOrder['OPAuftragsposten'], $log_json_data);
                }
            }

            return json_encode($theOrders);
        }

        return __('No orders availlabe for upload to PayJoe', 'weslink-payjoe-opbeleg'); //new WP_Error('no_orders', __('Keine Betellungen für den Export vorhanden.', 'woocommerce-simply-order-export'));
    }

    /**
     * @type
     */
    private function getOpData($order_details, $invoice_data) {
        $invoice_number = $invoice_data['number'];
        $invoice_date = $invoice_data['date'];
        $invoice_type = $invoice_data['type'];
        $refund_order_id = $invoice_data['refund_order_id'];
        $address_order_id = $invoice_data['address_order_id'];

        $refund_details = null;
        if ($refund_order_id > 0 && $order_details->ID != $refund_order_id) {
            $refund_details = wc_get_order($refund_order_id);
        }

        // Use different order for address if needed
        $address_details = $order_details;
        if ($address_order_id > 0 && $order_details->ID != $address_order_id) {
            $address_details = wc_get_order($address_order_id);
        }

        // Since 1.5.0 for germanized
        $invoice_id = $invoice_data['id'];
        $invoice_number_formatted = $invoice_data['number_formatted'];

        if ($invoice_date) {
            $m_datetime = new DateTime($invoice_date);
            $invoice_date = $m_datetime->format('c');
        } else {
            $invoice_date = $order_details->order_date;
        }

        // OPBeleg - invoice
        $currency_code = Weslink_Payjoe_Helpers::get_currency_code($order_details->get_currency());

        $obj_op_auftragsposten = array();
        $obj_op_auftragsposten['OPBeleg'] = array(
            'OPBelegZugangID' => intval(get_option('payjoe_zugangsid')),
            'OPBelegtyp' => $invoice_type, // 0= rechnung, 1= gutschrift
            'OPZahlungsArt' => $order_details->get_payment_method(),
            'OPBelegHerkunft' => "woocommerce",
            'OPBelegdatum' => $invoice_date,
            'OPBelegNummer' => $invoice_number,
            //_wcpdf_invoice_number
            'OPBelegKundenNr' => $order_details->get_customer_id(),
            // get_post_meta(get_the_ID, '_customer_user', true);
            //'OPBelegDebitorenNr'    =>  "", // ?
            'OPBelegBestellNr' => $order_details->get_order_number(),
            'OPBelegWaehrung' => intval($currency_code ? $currency_code : ''),
            // currency code
            //'OPBelegUstID'          =>  "", // VAT ID <--- is customer VAT ID ?
            'OPBelegTransaktionsID' => $order_details->get_transaction_id(),
            //'OPBelegFaelligBis'     =>  "", // ? due date
            // Post ID can be different from order number
            'OPBelegReferenz1' => $order_details->ID,
            'OPBelegReferenz2'      => $invoice_number_formatted,
            // 'OPBelegReferenz3'      =>  "",
            // 'OPBelegReferenz4'      =>  "",
            // 'OPBelegReferenz5'      =>  ""
        );

        // OPBelegLieferadresse - delivery address
        $obj_op_auftragsposten['OPBelegLieferadresse'] = array(
            'OPBelegAdresseLand' => $address_details->get_shipping_country(),
            'OPBelegAdresseFirma' => $address_details->get_shipping_company(),
            'OPBelegAdresseName' => $address_details->get_shipping_last_name(),
            'OPBelegAdresseVorname' => $address_details->get_shipping_first_name(),
            'OPBelegAdresseEmail' => $address_details->get_billing_email(),
            //not available, so we reuse here the billing address
            'OPBelegAdresseStrasse' => $address_details->get_shipping_address_1() . ' ' . $address_details->get_shipping_address_2(),
            'OPBelegAdressePLZ' => $address_details->get_shipping_postcode(),
            'OPBelegAdresseOrt' => $address_details->get_shipping_city()
        );

        // OPBelegRechnungsadresse - billing address
        $obj_op_auftragsposten['OPBelegRechnungsadresse'] = array(
            'OPBelegAdresseLand' => $address_details->get_billing_country(),
            'OPBelegAdresseFirma' => $address_details->get_billing_company(),
            'OPBelegAdresseName' => $address_details->get_billing_last_name(),
            'OPBelegAdresseVorname' => $address_details->get_billing_first_name(),
            'OPBelegAdresseEmail' => $address_details->get_billing_email(),
            'OPBelegAdresseStrasse' => $address_details->get_billing_address_1() . ' ' . $address_details->get_billing_address_2(),
            'OPBelegAdressePLZ' => $address_details->get_billing_postcode(),
            'OPBelegAdresseOrt' => $address_details->get_billing_city()
        );

        if (strlen(trim($obj_op_auftragsposten['OPBelegLieferadresse']['OPBelegAdresseLand'])) == 0) {
            $obj_op_auftragsposten['OPBelegLieferadresse']['OPBelegAdresseLand'] = $obj_op_auftragsposten['OPBelegRechnungsadresse']['OPBelegAdresseLand'];
        }

        // For the OP positons the refund is relevant
        if ($refund_details) {
            $order_details = $refund_details;
        }

        // OPBelegpositionen - invoice items
        $obj_op_auftragsposten['OPBelegpositionen'] = array();

        // create OPBelegposition Objects
        $total_net_amount = 0;
        $items = $order_details->get_items();
        $taxes = $order_details->get_taxes();

        $OPBelegpositions = array();

        // tax group
        foreach ($items as $item_id => $item) {
            $line_tax_data = isset($item['line_tax_data']) ? $item['line_tax_data'] : '';
            $tax_data = maybe_unserialize($line_tax_data);
            $item_total = $item['line_total'];

            // in case discount/coupon is applied, use 'subtotal' instead
            if (isset($item['line_subtotal']) && $item['line_subtotal'] != $item['line_total']) {
                $item_total = $item['line_subtotal'];
            }

            $tax_item_id = 0;
            $tax_item_total = 0;
            foreach ($taxes as $tax_item) {
                $tmp_rate_id = $tax_item['rate_id'];
                $tax_item_total = isset($tax_data['total'][$tmp_rate_id]) ? $tax_data['total'][$tmp_rate_id] : null;
                $tax_item_subtotal = isset($tax_data['subtotal'][$tmp_rate_id]) ? $tax_data['subtotal'][$tmp_rate_id] : null;

                if ($tax_item_total && $tax_item_subtotal) {
                    $tax_item_id = $tmp_rate_id;
                    // in case discount/coupon is applied, use 'subtotal' instead
                    if (isset($tax_item_subtotal) && $tax_item_subtotal != $tax_item_total) {
                        $tax_item_total = $tax_item_subtotal;
                    }

                    break;
                }
            }

            // guess tax rate
            $tax_rate = 0;
            if ($item_total && $tax_item_total) {
                $tax_rate = round(($tax_item_total / $item_total) * 100, 4);
            }

            // collect VAT Group info
            if (!is_array($OPBelegpositions)) {
                $OPBelegpositions = array();
            }
            if (!isset($OPBelegpositions[$tax_item_id])) {
                $OPBelegpositions[$tax_item_id] = array(
                    'OPBelegBuchungstext' => 0,
                    //'OPSteuerschluessel'        =>  '',
                    //'OPBelegKostenstelle'       =>  '',
                    //'OPBelegKostentraeger'      =>  '',
                    'OPBelegPostenGesamtNetto' => 0,
                    'OPBelegPostenGesamtBrutto' => 0,
                    'OPBelegSteuersatz' => $tax_rate,
                    //'OPBelegHabenKonto'         =>  ''
                );
            }

            // For refunds the amounts are negative. But pass positive values to the api
            $OPBelegpositions[$tax_item_id]['OPBelegPostenGesamtNetto'] += abs(round($item_total, 2));
            if (!is_numeric($tax_item_total)) {
                $OPBelegpositions[$tax_item_id]['OPBelegPostenGesamtBrutto'] = $OPBelegpositions[$tax_item_id]['OPBelegPostenGesamtNetto'];
            } else {
                $OPBelegpositions[$tax_item_id]['OPBelegPostenGesamtBrutto'] += abs(round($item_total + $tax_item_total, 2));
            }

            $total_net_amount += $item_total;
        }

        // discount / coupon amount
        $total_net_discounted_amount = $order_details->cart_discount;
        $discount_group = array();
        if ($total_net_amount != 0) {
            foreach ($OPBelegpositions as $tax_id => $each_OPBelegposition) {
                $posPercentOfTotal = $each_OPBelegposition['OPBelegPostenGesamtNetto'] / $total_net_amount;
                $posTax = $each_OPBelegposition['OPBelegSteuersatz'] / 100;
                $OPBelegPostenGesamtNetto = $posPercentOfTotal * $total_net_discounted_amount;
                $OPBelegPostenGesamtBrutto = $OPBelegPostenGesamtNetto * (1 + $posTax);
                if ($OPBelegPostenGesamtNetto == 0) {
                    //skip lines with 0-values
                    continue;
                }
                // Amounts are positive but PayJoe can handle that.
                $discount_group[$tax_id] = array(
                    'OPBelegBuchungstext' => 1,
                    //'OPSteuerschluessel'        =>  '',
                    //'OPBelegKostenstelle'       =>  '',
                    //'OPBelegKostentraeger'      =>  '',
                    'OPBelegPostenGesamtNetto' => abs(round($OPBelegPostenGesamtNetto, 2)),
                    'OPBelegPostenGesamtBrutto' => abs(round($OPBelegPostenGesamtBrutto, 2)),
                    'OPBelegSteuersatz' => $each_OPBelegposition['OPBelegSteuersatz']
                );
            }
        }

        /*
            * TODO: add seperate lines for each shipping and coupon tax class
            * currently the wc api gives us just one tax information for alle shipping item.
            */
        // shipping amount
        $total_shipping = $order_details->get_shipping_total();
        if ($total_shipping && $total_shipping != 0) {
            $shipping_tax = $order_details->get_shipping_tax();
            $shipping_gross = $total_shipping + $shipping_tax;
            $shipping_tax_rate = round(($shipping_tax / $total_shipping * 100), 2);
            $OPBelegpositions['shipping'] = array(
                'OPBelegBuchungstext' => 2,
                //'OPSteuerschluessel'        =>  '',
                //'OPBelegKostenstelle'       =>  '',
                //'OPBelegKostentraeger'      =>  '',
                'OPBelegPostenGesamtNetto' => abs($total_shipping),
                'OPBelegPostenGesamtBrutto' => abs($shipping_gross),
                'OPBelegSteuersatz' => $shipping_tax_rate,
                // 'OPBelegHabenKonto'         =>  '' //if this is not submitted it crashes
            );
        }

        // add discount info
        foreach ($discount_group as $each_discount_group) {
            array_push($OPBelegpositions, $each_discount_group);
        }

        $obj_op_auftragsposten['OPBelegpositionen'] = array_values($OPBelegpositions);

        return $obj_op_auftragsposten;
    }

    private function getInvoiceString($number, $date) {
        return "pjin_${number}_${date}";
    }

    public function mapWcpdfInvoiceNumbers($mapRecent = true) {
        $enable_log = get_option('payjoe_log');
        $transfer_count = (int)get_option('payjoe_transfer_count', 10);
        $start_order_date = get_option('payjoe_start_order_date');

        $recentOrders = [];
        if ($mapRecent) {
            // Process recently updated orders. Necessary to detect refunds.
            $query = new WC_Order_Query( array(
                'orderby' => 'modified',
                'order' => 'DESC',
                'return' => 'ids',
                //'parent' => 0,
                'date_modified' => '>' . ( time() - DAY_IN_SECONDS ),
                // See handle_payjoe_invoice_query_var
                '_payjoe_status' => [ 'compare' => 'EXISTS' ]
            ));

            $recentOrders = $query->get_orders();
            if($enable_log) {
                echo count($recentOrders) . " recent orders to preprocess.\n";
            }
        }

        // Unprocessed orders where the extracted invoice number is not set.
        // Do that in batches to decrease the initial load. Because it has
        // the same sort order but a bigger limit than the actual upload query
        // it is a given, that there are enough processed orders.
        $args = array(
            'limit' => $transfer_count * 2,
            'orderby' => 'ID',
            'order' => 'ASC',
            'return' => 'ids',
            //'parent' => 0,
            'exclude' => $recentOrders,
            // See handle_payjoe_invoice_query_var
            '_payjoe_status' => [ 'compare' => 'NOT EXISTS' ]
        );
        if ($start_order_date) {
            $args['date_created'] = '>=' . $start_order_date;
        }

        $query = new WC_Order_Query( $args );

        $unprocessedOrders = $query->get_orders();
        echo(count($unprocessedOrders) . " unpreprocessed orders.\n");

        $orders = array_merge($unprocessedOrders, $recentOrders);

        if($enable_log) {
            echo('Preprocessing ' . count($orders) . " orders\n");
        }

        foreach($orders as $order) {
            $invoice = wcpdf_get_invoice($order);
            if (!$invoice) {
                update_post_meta($order, '_payjoe_status', PAYJOE_STATUS_PENDING);
                continue;
            }

            $number = $invoice->get_number();
            if (!$number) {
                update_post_meta($order, '_payjoe_status', PAYJOE_STATUS_PENDING);
                continue;
            }

            echo("Order $order:\n");

            $invoiceData = [];

            $invoice_id = $order;
            $invoice_number = $number->number;
            $invoice_formatted = $number->formatted_number;
            $invoice_date = date_format($invoice->get_date(), 'c');

            if($enable_log) {
                echo("    InvoiceDate: $invoice_date, InvoiceId $invoice_id, Number: $invoice_number, Formatted: $invoice_formatted \n");
            }

            $invoiceData[] = [
                'id' => $invoice_id,
                'number' => $invoice_number,
                'number_formatted' => $invoice_formatted,
                //'refund_order_id' => $invoice->get_refund_parent_id($order),
                'date' => $invoice_date,
                // Refund or not
                'type' => $invoice->is_refund($order) ? 1 : 0,
            ];

            $this->preprocessUpdatePostMeta($order, $invoiceData, $enable_log);
        }
    }

    /*
     * Maps the Germanized Invoice numbers to _payjoe_invoice_number. That
     * way the upload code can just use standardized fields for the different
     * invoicing plugins.
     */
    public function mapGermanizedInvoiceNumber($mapRecent = true)
    {
        $enable_log = get_option('payjoe_log');
        $transfer_count = (int)get_option('payjoe_transfer_count', 10);
        $start_order_date = get_option('payjoe_start_order_date');

        $recentOrders = [];
        if ($mapRecent) {
            // Process recently updated orders. Necessary to detect refunds.
            $query = new WC_Order_Query( array(
                'orderby' => 'modified',
                'order' => 'DESC',
                'return' => 'ids',
                'parent' => 0,
                'date_modified' => '>' . ( time() - DAY_IN_SECONDS ),
                // See handle_payjoe_invoice_query_var
                '_payjoe_status' => [ 'compare' => 'EXISTS' ]
            ));

            $recentOrders = $query->get_orders();
            if($enable_log) {
                echo count($recentOrders) . " recent orders to preprocess.\n";
            }
        }

        // Unprocessed orders where the extracted invoice number is not set.
        // Do that in batches to decrease the initial load. Because it has
        // the same sort order but a bigger limit than the actual upload query
        // it is a given, that there are enough processed orders.
        $args = array(
            'limit' => $transfer_count * 2,
            'orderby' => 'ID',
            'order' => 'ASC',
            'return' => 'ids',
            'parent' => 0, // We get all the invoices from the parent
            'exclude' => $recentOrders,
            // See handle_payjoe_invoice_query_var
            '_payjoe_status' => [ 'compare' => 'NOT EXISTS' ]
        );
        if ($start_order_date) {
            $args['date_created'] = '>=' . $start_order_date;
        }

        $query = new WC_Order_Query( $args );


        $unprocessedOrders = $query->get_orders();
        echo(count($unprocessedOrders) . " unpreprocessed orders.\n");

        $orders = array_merge($unprocessedOrders, $recentOrders);

        if($enable_log) {
            echo('Preprocessing ' . count($orders) . " orders\n");
        }

        foreach($orders as $order) {
            // Returns the documents only for the main order but will return nothing if
            // the refund is passed. Check the invoices refund_order_id to see if it belongs
            // to a refund.
            $invoices = wc_gzdp_get_invoices_by_order($order);
            $invoiceData = [];

            if($enable_log) {
                echo("Order $order:\n");
            }

            foreach ($invoices as $invoice) {
                $invoice_formatted = $invoice->formatted_number;
                $invoice_date = date_format($invoice->get_document()->get_date_created(), 'c');
                $invoice_id = $invoice->id;
                $invoice_number = $invoice->number;

                if($enable_log) {
                    echo("    InvoiceDate: $invoice_date, InvoiceId $invoice_id, Number: $invoice_number, Formatted: $invoice_formatted \n");
                }

                $invoiceData[] = [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'number_formatted' => $invoice->formatted_number,
                    'refund_order_id' => $invoice->refund_order_id,
                    'date' => $invoice_date,
                    // Refund or not
                    'type' => $invoice->type == 'cancellation' ? 1 : 0,
                ];
            }

            $this->preprocessUpdatePostMeta($order, $invoiceData, $enable_log);
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function mapGermanMarketInvoiceNumber($mapRecent = true)
    {
        $enable_log = get_option('payjoe_log');
        $transfer_count = (int)get_option('payjoe_transfer_count', 10);
        $start_order_date = get_option('payjoe_start_order_date');

        $recentOrders = [];
        if ($mapRecent) {
            // Process recently updated orders. Necessary to detect refunds.
            $query = new WC_Order_Query( array(
                'orderby' => 'modified',
                'order' => 'DESC',
                'return' => 'ids',
                'date_modified' => '>' . ( time() - DAY_IN_SECONDS ),
                // See handle_payjoe_invoice_query_var
                '_payjoe_status' => [ 'compare' => 'EXISTS' ]
            ));

            $recentOrders = $query->get_orders();
            if($enable_log) {
                echo count($recentOrders) . " recent orders to preprocess.\n";
            }
        }

        // Unprocessed orders where the extracted invoice number is not set.
        // Do that in batches to decrease the initial load. Because it has
        // the same sort order but a bigger limit than the actual upload query
        // it is a given, that there are enough processed orders.
        $args = array(
            'limit' => $transfer_count * 2,
            'orderby' => 'ID',
            'order' => 'ASC',
            'return' => 'ids',
            'exclude' => $recentOrders,
            // See handle_payjoe_invoice_query_var
            '_payjoe_status' => [ 'compare' => 'NOT EXISTS' ]
        );
        if ($start_order_date) {
            $args['date_created'] = '>=' . $start_order_date;
        }

        $query = new WC_Order_Query( $args );

        $unprocessedOrders = $query->get_orders();
        echo(count($unprocessedOrders) . " unpreprocessed orders.\n");

        $orders = array_merge($unprocessedOrders, $recentOrders);

        if($enable_log) {
            echo('Preprocessing ' . count($orders) . " orders\n");
        }

        if (!count($orders)) {
            return false;
        }

        // Change Placeholdes in German Market Invoice String
        $placeholder_date_time = new DateTime();
        $search = array('{{year}}', '{{year-2}}', '{{month}}', '{{day}}');
        $replace = array($placeholder_date_time->format('Y'), $placeholder_date_time->format('y'), $placeholder_date_time->format('m'), $placeholder_date_time->format('d'));
        $prefix_length = strlen(str_replace($search, $replace, get_option('wp_wc_running_invoice_number_prefix', '')));
        $suffix_length = strlen(str_replace($search, $replace, get_option('wp_wc_running_invoice_number_suffix', '')));

        $prefix_refund_length = strlen(str_replace($search, $replace, get_option('wp_wc_running_invoice_number_prefix_refund', '')));
        $suffix_refund_length = strlen(str_replace($search, $replace, get_option('wp_wc_running_invoice_number_suffix_refund', '')));

        foreach($orders as $order) {
            $invoiceData = [];

            if($enable_log) {
                echo("Order $order:\n");
            }

            $german_market_invoice_number = get_post_meta($order, '_wp_wc_running_invoice_number', true);
            $german_market_invoice_date = get_post_meta($order, '_wp_wc_running_invoice_number_date', true);
            $address_order_id = $order;
            $is_refund = get_post_meta($order, '_refund_amount', true);

            if ($german_market_invoice_number) {
                if($enable_log) {
                    echo("    InvoiceDate: $german_market_invoice_date, InvoiceId $order, Formatted: $german_market_invoice_number \n");
                }

                $pl = $prefix_length;
                $sl = $suffix_length;

                // Refunds need special handling because they can have different pre and suffixes and also
                // have no date metadata field.
                if ($is_refund) {
                    $refund_details = new WC_Order($order);
                    $parent_id = $refund_details->get_parent_id();
                    $german_market_invoice_date = $refund_details->get_date_created()->getTimestamp();

                    $parent_invoice_number = get_post_meta($parent_id, '_wp_wc_running_invoice_number', true);

                    // Use address from parent order
                    $address_order_id = $parent_id;

                    $pl = $prefix_refund_length;
                    $sl = $suffix_refund_length;

                    echo("    Is a refund, parent order: $parent_id - $parent_invoice_number ($german_market_invoice_date)\n");
                }

                // Extract the running number: INV-123-2020 -> 123
                $payjoe_invoice_number = $german_market_invoice_number;
                if ($pl > 0) {
                    $payjoe_invoice_number = substr($payjoe_invoice_number, $pl);
                }
                if ($sl > 0) {
                    $payjoe_invoice_number = substr($payjoe_invoice_number, 0, -$sl);
                }

                $payjoe_invoice_number = (int)$payjoe_invoice_number;

                if($enable_log) {
                    echo("    Extracted running number: $payjoe_invoice_number\n");
                }

                $invoiceData[] = [
                    'id' => $order,
                    'number' => $payjoe_invoice_number,
                    'number_formatted' => $german_market_invoice_number,
                    'address_order_id' => $address_order_id,
                    'date' => date('c', $german_market_invoice_date),
                    'type' => $is_refund ? 1 : 0,
                ];
            }

            $this->preprocessUpdatePostMeta($order, $invoiceData, $enable_log);
        }

        return false;
    }

    private function preprocessUpdatePostMeta($order, $invoiceData, $enable_log) {
        $maxInvoiceId = -1;
        $maxInvoiceDate = null;
        $maxInvoiceNumber = null;

        foreach($invoiceData as $inv) {
            if ($inv['id'] > $maxInvoiceId) {
                $maxInvoiceId = $inv['id'];
                $maxInvoiceDate = $inv['date'];
                $maxInvoiceNumber = $inv['number'];
            }
        }

        // Make a string with all invoice numbers and dates that allows
        // direct DB queries with LIKE.
        // WHERE _payjoe_invoice_string LIKE '%pjin_123_2020-10-12T14:43:18%'
        $func = function($value) { return $this->getInvoiceString($value['number'], $value['date']); };
        $invoiceString = join(';', array_map($func, $invoiceData));

        if($enable_log) {
            echo("    -> Most recent InvoiceId: $maxInvoiceId \n");
        }

        // If there are new invoices or cancellations the invoice string will change and
        // the state of the order will be resetted so that the upload code will pick up the
        // order again.
        $prevInvoiceString = get_post_meta($order, '_payjoe_invoice_string', true);
        if (update_post_meta($order, '_payjoe_invoice_string', $invoiceString, $prevInvoiceString)) {
            if($enable_log) {
                echo("    -> '$prevInvoiceString' changed to '$invoiceString'\n");
            }

            // Reset state on value change, otherwise it will not be picked up for upload
            update_post_meta($order, '_payjoe_status', PAYJOE_STATUS_PENDING);

            // Array with available invoices for the order
            update_post_meta($order, '_payjoe_invoices', $invoiceData);

            // Set these to the most recent invoice for filtering and backwards compatibility.
            // _payjoe_invoice_number is also used to check for unprocessed orders.
            update_post_meta($order, '_payjoe_invoice_id', $maxInvoiceId);
            update_post_meta($order, '_payjoe_invoice_date', $maxInvoiceDate);
            update_post_meta($order, '_payjoe_invoice_number', $maxInvoiceNumber);
        }
	}

    /**
     * @param $result
     * @param $order_positions
     * @param bool $log_json_data
     */
    public function handleAPIResult($result, $order_positions, $log_json_data = false)
    {
        $tplError = "<span style='color:red'>%s: %s</span>\n";
        $tplSuccess = "<span style='color:green'>%s</span>\n";
        $result = trim($result);

        if ($result) {
            $result = json_decode($result, true);

            if (!$result) {
                echo (json_last_error_msg());
                return;
            }
        }

        // The same order might have different results for different invoices.
        // Assume success.
        $orderResults = [];
        $orderPostIdMapping = [];
        foreach ($order_positions as $aOrderPos) {
            $postId = $aOrderPos['OPBeleg']['OPBelegReferenz1'];
            $orderNumber = $aOrderPos['OPBeleg']['OPBelegBestellNr'];
            $orderResults[$orderNumber] = [];
            $orderPostIdMapping[$orderNumber] = $postId;
        }

        if(isset($result['Fehlerliste'])) {
            foreach($result['Fehlerliste'] as $errorEntry) {
                $opNumber = $errorEntry['OPBelegNummer'];
                $opDate = $errorEntry['OPBelegDatum'];
                $opDateObject = new DateTime($opDate);

                $reasons = $errorEntry['OPBelegErrorReasons'];

                if (!$errorEntry['OPBelegNummer']) {
                    echo(sprintf($tplError, __("Error"), json_encode($reasons)));
                    continue;
                }

                $orderNumber = null;

                // Find the order that belongs to the OP Beleg
                foreach($order_positions as $uploadEntry) {
                    $currentNumber = $uploadEntry['OPBeleg']['OPBelegNummer'];
                    $currentDate = new DateTime($uploadEntry['OPBeleg']['OPBelegdatum']);
                    $currentOrderNumber = $uploadEntry['OPBeleg']['OPBelegBestellNr'];

                    if ($opNumber != $currentNumber) {
                       continue;
                    }

                    $diff = $opDateObject->diff($currentDate);
                    if ($diff->h == 0 && $diff->m == 0) {
                        $orderNumber = $currentOrderNumber;
                        break;
                    }
                }

                if (!$orderNumber) {
                    echo(sprintf($tplError, __("Error"), "Order for $opNumber ($opDate) not found."));
                    continue;
                }

                $success = false;
                $msg = "  Invoice $opNumber ($opDate):";
                if (isSet($reasons) && count($reasons) > 0) {
                    foreach($reasons as $reason) {
                        // Reason == 1 means that it was uploaded before.
                        // Don't treat as error
                        if ($reason['OPBelegErrorReason'] == 1) {
                            $msg = 'Duplicate';
                            $success = true;
                            break;
                        }

                        $msg .= "\n  - " . $reason['OPBelegErrorText'];
                    }
                }

                $orderResults[$orderNumber][] = ['success' => $success, 'msg' => $msg];
            }
        }

        // Go through the collected results for each order and check if there is an error.
        foreach($orderResults as $orderNumber => $orderResultEntry) {
            $result = ['error' => null]; // Success
            $success = true;
            foreach($orderResultEntry as $orderResult) {
                if (!$orderResult['success']) {
                    $result['error'] .= $orderResult['msg'];
                    $success = false;
                    break;
                }
            }

            $postId = $orderPostIdMapping[$orderNumber];

            if ($success) {
                echo(sprintf($tplSuccess, "Order $orderNumber (Post ID $postId): all invoices transferred successfully."));
            } else {
                echo(sprintf($tplError, __("Error"), "Order $orderNumber (Post ID $postId):\n" . $result['error']));
            }

            do_action('weslink-payjoe-opbeleg-post-upload', $result, $postId);
        }

        /*
        if ($maxInvoiceId) {
            //do_action('weslink-payjoe-opbeleg-update-last-processed', $msg, $order_number, $maxInvoiceId);
        }
        */
    }

    /**
     * @return boolean|array
     */
    public function getOrderByBelegnummer($belegnummer, $date)
    {
        $order_without_error = get_posts(array(
            'post_type' => 'shop_order',
            'post_status' => array_keys(wc_get_order_statuses()),
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_payjoe_invoice_string',
                    'compare' => 'LIKE',
                    'value' => '%;'. $belegnummer . '_' . $date .';%',
                ),
                array(
                    array(
                        'key' => '_payjoe_invoice_number',
                        'value' => $belegnummer
                    ),
                    array(
                        'key' => '_payjoe_invoice_date',
                        'value' => $date
                    ),
                )
            )
        ));

        if (!empty($order_without_error)) {
            $order_number = $order_without_error[0]->ID;
            $invoice_number = get_post_meta($order_without_error[0]->ID, '_payjoe_invoice_number', true);

            return [$order_number, $invoice_number];
        }

        return false;
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function uploadBelegtoPayJoe($data)
    {
        $url = 'https://api.payjoe.de/api/opbelegupload';
        $curl = curl_init($url);
        $data = json_encode($data);

        $options = array(
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);
		if (!$result) {
            $info = curl_getinfo($curl);
            $tplError = "<span style='color:red'>%s: %s</span>\n";
            $msg = __('Unknown error occurred!');
            switch($info['http_code']) {
                case 0:
                    $msg = __('The PayJoe servers are not reachable.');
                    break;
                case 401:
                    $msg = __('Invalid PayJoe credentials.');
                    break;
            }
            echo(sprintf($tplError, __("Error"), $msg . "\n"));
        }
        curl_close($curl);

        return $result;
    }


    /**
     * @param $message
     */
    public function sendErrorNotificationToAdmin($message)
    {
        $message = "This is an automatic email from the Woocommerce PayJoe Plugin. There has been an error with the Pajoe Upload: \n \n'.$message.'\n If you have enabled debugging, you can check the logfiles at uploads/payjoe/ to get more information.";
        $to = get_bloginfo('admin_email');
        $subject = 'PayJoe upload error at ' . get_home_url();
        wp_mail($to, $subject, $message);
    }

    /**
     * @throws Exception
     */
    public function setResendStatus()
    {
        delete_post_meta_by_key('_payjoe_status');
        delete_post_meta_by_key('_payjoe_error');

        delete_post_meta_by_key('_payjoe_invoice_date');

        // Old field
        delete_post_meta_by_key('_payjoe_invoice_number');

        // new Fields
        delete_post_meta_by_key('_payjoe_invoices');
        delete_post_meta_by_key('_payjoe_invoice_id');
        delete_post_meta_by_key('_payjoe_invoice_string');
    }

    /**
     * Decide which plugin is used for Invoicing
     *  get option
     *  '0'    =>    'WP Overnight WooCommerce PDF Invoices & Packing Slips',
     *  '1'    =>    'WooCommerce Germanized',
     *  '2'    =>    'GermanMarket'
     * @return array
     * @throws Exception
     */
    public function getInvoiceCustomFieldKeys()
    {
        $type = (int)get_option('payjoe_invoice_options');

        $mapRecent = true;
        $invoice_number_field_key = '_payjoe_invoice_number';
        $invoice_date_field_key = '_payjoe_invoice_date';

        switch($type) {
            case 0:
                $this->mapWcpdfInvoiceNumbers($mapRecent);
                break;
            case 1:
                $this->mapGermanizedInvoiceNumber($mapRecent);
                break;
            case 2:
                $this->mapGermanMarketInvoiceNumber($mapRecent);
                break;
        }

        return array($invoice_number_field_key, $invoice_date_field_key);
    }
}
