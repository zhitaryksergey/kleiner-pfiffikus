<?php

class WC_GZDP_VAT_Validation {
	
	private $api_url = "http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl";

	private $client = null;

	private $options = array(
	    'debug'            => false,
        'requester_vat_id' => ''
    );

	private $valid = false;

	private $data= array();
	
	public function __construct( $options = array() ) {
		
		foreach( $options as $option => $value ) {
			$this->options[ $option ] = $value;
        }

		if ( ! class_exists( 'SoapClient' ) ) {
			wp_die( __( 'SoapClient is required to enable VAT validation', 'woocommerce-germanized-pro' ) );
        }

		try {
			$this->client = new SoapClient( $this->api_url, array( 'trace' => true ) );
		} catch( Exception $e ) {
			$this->valid = false;
		}
	}

	public function check( $country, $nr ) {
		$rs = null;

		if ( $this->client ) {
            try {
                $args = array(
                    'countryCode' => $country,
                    'vatNumber'   => $nr
                );

                if ( ! empty( $this->options['requester_vat_id'] ) ) {
                    $request_number               = WC_GZDP_VAT_Helper::instance()->get_vat_id_from_string( $this->options['requester_vat_id'] );
                    $args['requesterCountryCode'] = $request_number['country'];
                    $args['requesterVatNumber']   = $request_number['number'];
                }

                $rs = $this->client->checkVatApprox( $args );

                if( $rs->valid ) {
                    $this->valid = true;
                    $this->data  = array(
                        'name' 		   => $this->parse_string( isset( $rs->name ) ? $rs->name : '' ),
                        'identifier'   => $this->parse_string( isset( $rs->requestIdentifier ) ? $rs->requestIdentifier : '' ),
                        'company'      => $this->parse_string( isset( $rs->traderName ) ? $rs->traderName : '' ),
                        'address'      => $this->parse_string( isset( $rs->traderAddress ) ? $rs->traderAddress : '' ),
                        'date'         => date_i18n( 'Y-m-d H:i:s' ),
                        'raw'          => (array) $rs,
                    );
                } else {
                    $this->valid = false;
                    $this->data = array();
                }

            } catch( SoapFault $e ) {
                $this->valid = false;
                $this->data  = array();
            }
        }

    	return apply_filters( 'woocommerce_gzdp_vat_validation_result', $this->valid, $country, $nr, $rs );
	}

	public function is_valid() {
		return $this->valid;
	}
	
	public function get_name() {
	    return isset( $this->data['name'] ) ? $this->data['name'] : '';
	}

    public function get_company() {
        return isset( $this->data['company'] ) ? $this->data['company'] : '';
    }

    public function get_identifier() {
        return isset( $this->data['identifier'] ) ? $this->data['identifier'] : '';
    }

    public function get_date() {
        return isset( $this->data['date'] ) ? $this->data['date'] : '';
    }

    public function get_raw() {
	    return isset( $this->data['raw'] ) ? $this->data['raw'] : '';
    }

    public function get_data() {
	    return $this->data;
    }
	
	public function get_address() {
		return isset( $this->data['address'] ) ? $this->data['address'] : '';
	}
	
	public function is_debug() {
		return ( $this->options['debug'] === true );
	}

	private function parse_string( $string ) {
    	return ( $string != "---" ? $string : false );
	}
}

?>