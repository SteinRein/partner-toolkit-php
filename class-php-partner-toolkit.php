<?php

/**
 * SteinRein PHP Partner Toolkit
 *
 * PHP helpers to provide simple tools for implementing
 * your SteinRein Partnership features.
 */

namespace SteinRein\Partner;

/**
 * To implement features from the Partner Toolkit, you need to add the following code:
 *
 * <?php
 *
 * $partner_id = {{YOUR_PARTNER_ID}};
 * $form_id = {{YOUR_FORM_ID}};
 * $form_api_key = {{YOUR_FORM_API_KEY}};
 *
 * // Configure the Form
 * // Only required if you want to update the default configuration.
 * // In most cases this can be omitted
 * $configuration = new SteinRein\Partner\PHP_Partner_Toolkit_Configuration();
 *
 * $configuration->set_form_config([
 *   'lang'            => {{ISO_TWO_LETTER_LANGUAGE_CODE}},
 *   'exclude_branch'  => {{COMMA_SEPERATED_LIST_OF_BRANCH_IDS}},
 *   'gmaps_api_key'   => {{YOUR_GOOGLE_MAPS_API_KEY}},
 * ]);
 *
 * $configuration->set_certificate_config([
 *   'cssPrefix'       => {{CSS_PREFIX}}, // default 'sr-certificate'
 *   'position'        => {{POSITION}}, // default 'top-right', accepted values are 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right'
 * ]);
 *
 * // With Configuration
 * $steinrein_partner_toolkit = new SteinRein\Partner\PHP_Partner_Toolkit($partner_id, $form_id, $form_api_key, $configuration);
 *
 * // Without Configuration
 * $steinrein_partner_toolkit = new SteinRein\Partner\PHP_Partner_Toolkit($partner_id, $form_id, $form_api_key);
 *
 * // Display the Form:
 * //
 * // Add the following code to your template:
 * $steinrein_partner_toolkit->display_form_page_content();
 *
 * // Add the following code before the closing </body> tag of your layout:
 * $steinrein_partner_toolkit->display_form_script();
 *
 * // Display the Certificate:
 * //
 * // Add the following code before the closing </body> tag of your layout:
 * $steinrein_partner_toolkit->display_certificate_script();
 *
 */

final class PHP_Partner_Toolkit
{
    public int $partner_id;
    public int $form_id;
    public string $form_api_key;
    public ?PHP_Partner_Toolkit_Configuration $configuration;

    public function __construct(
        int $partner_id,
        int $form_id,
        string $form_api_key,
        ?PHP_Partner_Toolkit_Configuration $configuration = null
    ) {
        $this->partner_id = $partner_id;
        $this->form_id = $form_id;
        $this->form_api_key = $form_api_key;
        $this->configuration = $configuration;
    }

    public function configure($configuration)
    {
        $this->configuration = new PHP_Partner_Toolkit_Configuration($configuration);
    }

    public function get_form_page_content()
    {
        // Fetch text
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://partner.steinrein.com/api/form-page.json',
            CURLOPT_USERAGENT => 'SteinRein Inquiry Form',
            CURLOPT_METHOD => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            )
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $page = '';

        if ($response) {
            $response = json_decode($response, true);
            if ($response['success']) {
                $page_content = $response['data'];
                $page = $page_content['intro'];
                $page .= '<div id="steinrein-form"></div>';

                $content_sections = $page_content['sections'];

                if (!empty($content_sections)) {
                    foreach ($content_sections as $content_section) {
                        ob_start();
                        ?>
                        <div class="steinrein--layout-alternating-block">
                            <div class="steinrein--layout-alternating-column">
                                <a href="<?php echo $content_section['link']; ?>" target="_blank">
                                    <img src="<?php echo $content_section['image']; ?>" alt="<?php echo $content_section['title']; ?>">
                                </a>
                            </div>
                            <div class="steinrein--layout-alternating-column">
                                <h3><a href="<?php echo $content_section['link']; ?>" target="_blank"><?php echo $content_section['title']; ?></a></h3>
                                <?php echo $content_section['text']; ?>
                            </div>
                        </div>
                        <?php
                        $page .= ob_get_clean();
                    }
                }

                $page .= '<style type="text/css">
                .steinrein--layout-alternating-block {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 35px;
                    align-items: center;
                    margin-bottom: 60px;
                }
                .steinrein--layout-alternating-block:nth-child(even) *:nth-child(1) {
                    grid-column: 2;
                }
                .steinrein--layout-alternating-block:nth-child(even) *:nth-child(2) {
                    grid-column: 1;
                    grid-row: 1;
                }
                .steinrein--layout-alternating-column img {
                    width: 100% !important;
                    display: block;
                }
                .steinrein--partner-voucher-code {
                    background-color:#eee;
                    border:1px solid #b4b4b4;
                    border-radius:3px;
                    box-shadow:0 1px 1px rgba(0,0,0,.2),inset 0 2px 0 0 hsla(0,0%,100%,.7);
                    color:#333;
                    display:inline-block;
                    font-size:.85em;
                    font-weight:700;
                    line-height:1;
                    padding:2px 4px;
                    white-space:nowrap;
                }

                @media (max-width: 800px) {
                    .steinrein--layout-alternating-block {
                    grid-template-columns: 1fr;
                    justify-items: center;
                    }
                    .steinrein--layout-alternating-block:nth-child(even) *:nth-child(1) {
                    grid-column: unset;
                    }
                    .steinrein--layout-alternating-block:nth-child(even) *:nth-child(2) {
                    grid-row: unset;
                    }
                }
                </style>';
            }
        }

        return $page;
    }

    public function display_form_page_content()
    {
        echo $this->get_form_page_content();
    }

    public function get_form_script_src() {
        $query_args = [
            'form_id' => $this->form_id,
            'api_key' => $this->form_api_key,
        ];

        if ($this->configuration && $this->configuration->form_config) {
            if (isset($this->configuration->form_config['lang']) && $this->configuration->form_config['lang']) {
                $query_args['lang'] = $this->configuration->form_config['lang'];
            }

            if (isset($this->configuration->form_config['exclude_branch']) && $this->configuration->form_config['exclude_branch']) {
                $query_args['exclude_branch'] = $this->configuration->form_config['exclude_branch'];
            }

            if (isset($this->configuration->form_config['gmaps_api_key']) && $this->configuration->form_config['gmaps_api_key']) {
                $query_args['gmaps_api_key'] = $this->configuration->form_config['gmaps_api_key'];
            }
        }

        return 'https://partner.steinrein.com/api/form.js' . '?' . http_build_query($query_args);
    }

    public function display_form_script() {
        echo '<script src="' . $this->get_form_script_src() . '" defer></script>';
    }

    public function get_certificate_script_src() {
        return 'https://partner.steinrein.com/api/certificate/' . $this->partner_id . '/main.js';
    }

    public function display_certificate_script() {
        if ($this->configuration && $this->configuration->certificate_config) {
            echo '<script type="text/javascript">var SRCertOptions = ' . json_encode($this->configuration->certificate_config) . ';</script>';
        }
        echo '<script src="' . $this->get_certificate_script_src() . '" defer></script>';
    }
}

final class PHP_Partner_Toolkit_Configuration
{
    public array $form_config = [];
    public array $certificate_config = [];

    public function set_form_config(array $form_config)
    {
        $this->form_config = $form_config;
    }

    public function set_certificate_config(array $certificate_config)
    {
        $this->certificate_config = $certificate_config;
    }
}
