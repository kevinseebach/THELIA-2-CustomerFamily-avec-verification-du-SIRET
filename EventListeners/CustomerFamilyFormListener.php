<?php
/*************************************************************************************/
/*      This file is part of the module CustomerFamily                               */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace CustomerFamily\EventListeners;

use CustomerFamily\CustomerFamily;
use CustomerFamily\Model\CustomerCustomerFamilyQuery;
use CustomerFamily\Model\CustomerFamilyQuery;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Thelia\Action\BaseAction;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Translation\Translator;

class CustomerFamilyFormListener extends BaseAction implements EventSubscriberInterface
{
    /** 'thelia_customer_create' is the name of the form used to create Customers (Thelia\Form\CustomerCreateForm). */
    const THELIA_CUSTOMER_CREATE_FORM_NAME = 'thelia_customer_create';
    const THELIA_ADMIN_CUSTOMER_CREATE_FORM_NAME = 'thelia_customer_create';

    /**
     * 'thelia_customer_profile_update' is the name of the form used to update accounts
     * (Thelia\Form\CustomerProfileUpdateForm).
     */
    const THELIA_ACCOUNT_UPDATE_FORM_NAME = 'thelia_customer_profile_update';

    const CUSTOMER_FAMILY_CODE_FIELD_NAME = 'customer_family_code';

    const CUSTOMER_FAMILY_SIRET_FIELD_NAME = 'siret';

    const CUSTOMER_FAMILY_VAT_FIELD_NAME = 'vat';

    /** @var \Thelia\Core\HttpFoundation\Request */
    protected $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::FORM_AFTER_BUILD.'.'.self::THELIA_CUSTOMER_CREATE_FORM_NAME => array('addCustomerFamilyFieldsForRegister', 129),
            TheliaEvents::FORM_AFTER_BUILD.'.'.self::THELIA_ADMIN_CUSTOMER_CREATE_FORM_NAME => array('addCustomerFamilyFieldsForRegister', 129),
            TheliaEvents::FORM_AFTER_BUILD.'.'.self::THELIA_ACCOUNT_UPDATE_FORM_NAME  => array('addCustomerFamilyFieldsForUpdate', 129),
        );
    }

    /**
     * Callback used to add some fields to the Thelia's CustomerCreateForm.
     * It add two fields : one for the SIRET number and one for VAT.
     * @param TheliaFormEvent $event
     */
    public function addCustomerFamilyFieldsForRegister(TheliaFormEvent $event)
    {
        // Retrieving CustomerFamily choices
        $customerFamilyChoices = array();

        /** @var \CustomerFamily\Model\CustomerFamily $customerFamily */
        foreach (CustomerFamilyQuery::create()->find() as $customerFamily) {
            $customerFamilyChoices[$customerFamily->getCode()] = self::trans($customerFamily->getTitle());
        }

        // Building additional fields
        $event->getForm()->getFormBuilder()
            ->add(
                self::CUSTOMER_FAMILY_CODE_FIELD_NAME,
                'choice',
                array(
                    'constraints' => array(
                        new Constraints\Callback(
                            array(
                                'methods' => array(
                                    array(
                                        $this, 'checkCustomerFamily'
                                    )
                                )
                            )
                        ),
                        new Constraints\NotBlank(),
                    ),
                    'choices' => $customerFamilyChoices,
                    'empty_data' => false,
                    'required' => false,
                    'label' => self::trans('Customer family'),
                    'label_attr' => array(
                        'for' => 'customer_family_id',
                    ),
                    'mapped' => false,
                )
            )
            ->add(
                self::CUSTOMER_FAMILY_SIRET_FIELD_NAME,
                'text',
                array(
                  'constraints' => array(
                      new Constraints\Callback(
                          array(
                              'methods' => array(
                                  array(
                                      $this, 'checkSiret'
                                  )
                              )
                          )
                      )
                  ),
                    'required' => true,
                    'empty_data' => false,
                    'label' => self::trans('Siret number'),
                    'label_attr' => array(
                        'for' => 'siret'
                    ),
                    'mapped' => false,
                )
            )
            ->add(
                self::CUSTOMER_FAMILY_VAT_FIELD_NAME,
                'text',
                array(
                    'required' => true,
                    'empty_data' => false,
                    'label' => self::trans('Vat'),
                    'label_attr' => array(
                        'for' => 'vat'
                    ),
                    'mapped' => false,
                )
            )
        ;
    }

    /**
     * Callback used to add some fields to the Thelia's CustomerCreateForm.
     * It add two fields : one for the SIRET number and one for VAT.
     * @param TheliaFormEvent $event
     */
    public function addCustomerFamilyFieldsForUpdate(TheliaFormEvent $event)
    {
        // Adding new fields
        $customer = $this->request->getSession()->getCustomerUser();

        if (is_null($customer)) {
            // No customer => no account update => stop here
            return;
        }

        $customerCustomerFamily = CustomerCustomerFamilyQuery::create()->findOneByCustomerId($customer->getId());

        $cfData = array(
            self::CUSTOMER_FAMILY_CODE_FIELD_NAME  => (is_null($customerCustomerFamily) or is_null($customerCustomerFamily->getCustomerFamily())) ? '' : $customerCustomerFamily->getCustomerFamily()->getCode(),
            self::CUSTOMER_FAMILY_SIRET_FIELD_NAME => is_null($customerCustomerFamily) ? false : $customerCustomerFamily->getSiret(),
            self::CUSTOMER_FAMILY_VAT_FIELD_NAME   => is_null($customerCustomerFamily) ? false : $customerCustomerFamily->getVat(),
        );

        // Retrieving CustomerFamily choices
        $customerFamilyChoices = array();

        /** @var \CustomerFamily\Model\CustomerFamily $customerFamilyChoice */
        foreach (CustomerFamilyQuery::create()->find() as $customerFamilyChoice) {
            $customerFamilyChoices[$customerFamilyChoice->getCode()] = self::trans($customerFamilyChoice->getTitle());
        }


        // Building additional fields
        $event->getForm()->getFormBuilder()
            ->add(
                self::CUSTOMER_FAMILY_CODE_FIELD_NAME,
                'choice',
                array(
                    'constraints' => array(
                        new Constraints\Callback(array('methods' => array(
                            array($this, 'checkCustomerFamily')
                        ))),
                        new Constraints\NotBlank(),
                    ),
                    'choices' => $customerFamilyChoices,
                    'empty_data' => false,
                    'required' => false,
                    'label' => self::trans('Customer family'),
                    'label_attr' => array(
                        'for' => 'customer_family_id',
                    ),
                    'mapped' => false,
                    'data' => $cfData[self::CUSTOMER_FAMILY_CODE_FIELD_NAME],
                )
            )
            ->add(
                self::CUSTOMER_FAMILY_SIRET_FIELD_NAME,
                'text',
                array(
                  'constraints' => array(
                      new Constraints\Callback(
                          array(
                              'methods' => array(
                                  array(
                                      $this, 'checkSiret'
                                  )
                              )
                          )
                      )
                  ),
                    'required' => true,
                    'empty_data' => false,
                    'label' => self::trans('Siret number'),
                    'label_attr' => array(
                        'for' => 'siret'
                    ),
                    'mapped' => false,
                    'data' => $cfData[self::CUSTOMER_FAMILY_SIRET_FIELD_NAME],
                )
            )
            ->add(
                self::CUSTOMER_FAMILY_VAT_FIELD_NAME,
                'text',
                array(
                    'required' => true,
                    'empty_data' => false,
                    'label' => self::trans('Vat'),
                    'label_attr' => array(
                        'for' => 'vat'
                    ),
                    'mapped' => false,
                    'data' => $cfData[self::CUSTOMER_FAMILY_VAT_FIELD_NAME],
                )
            )
        ;
    }

    /**
     * Validate a field only if the customer family is valid
     *
     * @param string                    $value
     * @param ExecutionContextInterface $context
     */
    public function checkCustomerFamily($value, ExecutionContextInterface $context)
    {
        if (CustomerFamilyQuery::create()->filterByCode($value)->count() == 0) {
            $context->addViolation(self::trans('The customer family is not valid'));
        }
    }

    public function checkSiret($siret, ExecutionContextInterface $context)
    {
      if(strlen($siret)!=0){
    	if (strlen($siret) != 14) $context->addViolation(self::trans('Numéro de SIRET Invalide')); // le SIRET doit contenir 14 caractères
    	if (!is_numeric($siret)) $context->addViolation(self::trans('Numéro de SIRET Invalide')); // le SIRET ne doit contenir que des chiffres
    	// on prend chaque chiffre un par un
    	// si son index (position dans la chaîne en commence à 0 au premier caractère) est pair
    	// on double sa valeur et si cette dernière est supérieure à 9, on lui retranche 9
    	// on ajoute cette valeur à la somme totale
    	for ($index = 0; $index < 14; $index ++)
    	{
    		$number = (int) $siret[$index];
    		if (($index % 2) == 0) { if (($number *= 2) > 9) $number -= 9; }
    		$sum += $number;
    	}
    	// le numéro est valide si la somme des chiffres est multiple de 10
   	if (($sum % 10) != 0) $context->addViolation(self::trans('Numéro de SIRET Invalide'));


    $service_url = 'https://www.verif-siret.com/api/siret?siret='.$siret;
    $curl = curl_init($service_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $apiKey="VOTRE CLEF API DE VERIF-SIRET.COM";
    $secretKey="VOTRE CLEF SECRETE DE VERIF-SIRET.COM";
    curl_setopt($curl, CURLOPT_USERPWD, $apiKey . ':' . $secretKey);
    $server_output = curl_exec ($curl);
    curl_close ($curl);
    $jsonArray=json_decode($server_output,true);
    if(empty($jsonArray["array_return"]))$context->addViolation(self::trans('Numéro de SIRET non trouvé'));

      }

    }


    /**
     * Utility for translations
     * @param $id
     * @param array $parameters
     * @return string
     */
    protected static function trans($id, array $parameters = array())
    {
        return Translator::getInstance()->trans($id, $parameters, CustomerFamily::MESSAGE_DOMAIN);
    }
}
