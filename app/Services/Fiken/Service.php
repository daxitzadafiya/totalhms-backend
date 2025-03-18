<?php

namespace App\Services\Fiken;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class Service
{
    protected $fikenHttpHeader;
    protected $client;
    protected $baseUrl;
    protected $compnaySlug;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
        $setting = Setting::where('key', 'fiken_system')->where('is_disabled',1)->first();
        if(!$setting){
            return ['message' => 'please fiken setting update'];
        }
        $fikenKey = @$setting->value_details['fikenPersonalKey'];
        $this->compnaySlug = @$setting->value_details['fikenCompanySlug'];
        $this->baseUrl = config('app.fiken_api_url');

        $this->fikenHttpHeader = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $fikenKey,
        ];
    }


    // get company detils
    public function getCompany()
    {
        try {
            $response =  $this->client->request('GET', $this->baseUrl . '/companies/' . $this->compnaySlug, [
                'headers' => $this->fikenHttpHeader,
            ]);
            $companie = json_decode($response->getBody()->getContents(), true);

            return $companie;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }


    // get all invoices lists
    public function getinvoices()
    {
        try {
            $response =  $this->client->request('GET', $this->baseUrl . '/companies/' . $this->compnaySlug . '/invoices', [
                'headers' => $this->fikenHttpHeader,
            ]);
            $companieInvoices = json_decode($response->getBody()->getContents(), true);

            return $companieInvoices;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }


    // get invoices information
    public function getinvoice($number)
    {
        try {
            $response =  $this->client->request('GET', $this->baseUrl . '/companies/' . $this->compnaySlug . '/invoices/' . $number, [
                'headers' => $this->fikenHttpHeader,
            ]);
            $companieInvoice = json_decode($response->getBody()->getContents(), true);

            return $companieInvoice;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }


    // get contact information
    public function getContact($number)
    {
        try {
            $response =  $this->client->request('GET', $this->baseUrl . '/companies/' . $this->compnaySlug . '/contacts/' . $number, [
                'headers' => $this->fikenHttpHeader,
            ]);

            $companieInvoice = json_decode($response->getBody()->getContents(), true);

            return $companieInvoice;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }


    // get all products lists
    public function getProducts()
    {
        try {
            $response =  $this->client->request('GET', $this->baseUrl . '/companies/' . $this->compnaySlug . '/products', [
                'headers' => $this->fikenHttpHeader,
            ]);

            $companieProducts = json_decode($response->getBody()->getContents(), true);

            return $companieProducts;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    // get product information
    public function getProduct($number)
    {
        try {
            $response =  $this->client->request('GET', $this->baseUrl . '/companies/' . $this->compnaySlug . '/products/' . $number, [
                'headers' => $this->fikenHttpHeader,
            ]);

            $companieProducts = json_decode($response->getBody()->getContents(), true);

            return $companieProducts;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }


    // create product
    public function createProduct($product, $vat)
    {
        try {
            if ($vat == 25) {
                $vatType = "HIGH";
            } elseif ($vat == 15) {
                $vatType = "MEDIUM";
            } else {
                $vatType = "LOW";
            }

            $body = [
                "name" => $product['title'],
                "unitPrice" => $product['price'] * 100,
                "incomeAccount" => "3040",
                "vatType" => $vatType,
                "active" => true,
                "productNumber" => $product['fiken_product_number'],
                // "stock" => 5,
                "note" => $product['description']
            ];

            $response = $this->client->post($this->baseUrl . '/companies/' . $this->compnaySlug . '/products', [
                'headers' => $this->fikenHttpHeader,
                'body' => json_encode($body),
            ]);

            $response =  $this->client->request('GET', $response->getHeaders()['Location'][0], [
                'headers' => $this->fikenHttpHeader,
            ]);
            $companieProduct = json_decode($response->getBody()->getContents(), true);

            return $companieProduct;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    public function createProductAdditional($product, $vat)
    {
        try {
            if ($vat == 25) {
                $vatType = "HIGH";
            } elseif ($vat == 15) {
                $vatType = "MEDIUM";
            } else {
                $vatType = "LOW";
            }

            $body = [
                "name" => $product['title'] . ' - Additional',
                "unitPrice" => $product['additional_price'] * 100,
                "incomeAccount" => "3040",
                "vatType" => $vatType,
                "active" => true,
                "productNumber" => $product['fiken_product_number'],
                // "stock" => 5,
                "note" => $product['description']
            ];

            $response = $this->client->post($this->baseUrl . '/companies/' . $this->compnaySlug . '/products', [
                'headers' => $this->fikenHttpHeader,
                'body' => json_encode($body),
            ]);

            $response =  $this->client->request('GET', $response->getHeaders()['Location'][0], [
                'headers' => $this->fikenHttpHeader,
            ]);
            $companieProduct = json_decode($response->getBody()->getContents(), true);

            return $companieProduct;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    // update product
    public function updateProduct($number, $product, $vat)
    {
        try {
            if ($vat == 25) {
                $vatType = "HIGH";
            } elseif ($vat == 15) {
                $vatType = "MEDIUM";
            } else {
                $vatType = "LOW";
            }
            $body = [
                "name" => $product['title'],
                "unitPrice" => $product['price'] * 100,
                "incomeAccount" => "3040",
                "vatType" => $vatType,
                "active" => true,
                "productNumber" => $product['fiken_product_number'],
                // "stock" => 5,
                "note" => $product['description']
            ];
      
            $response = $this->client->put($this->baseUrl . '/companies/' . $this->compnaySlug . '/products/' . $number, [
                'headers' => $this->fikenHttpHeader,
                'body' => json_encode($body),
            ]);

            $response =  $this->client->request('GET', $response->getHeaders()['Location'][0], [
                'headers' => $this->fikenHttpHeader,
            ]);
            $companieProduct = json_decode($response->getBody()->getContents(), true);

            return $companieProduct;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    public function updateProductAdditional($number, $product, $vat)
    {
        try {
            if ($vat == 25) {
                $vatType = "HIGH";
            } elseif ($vat == 15) {
                $vatType = "MEDIUM";
            } else {
                $vatType = "LOW";
            }

            $body = [
                "name" => $product['title'] . ' - Additional',
                "unitPrice" => $product['additional_price'] * 100,
                "incomeAccount" => "3040",
                "vatType" => $vatType,
                "active" => true,
                "productNumber" => $product['fiken_product_number'],
                // "stock" => 5,
                "note" => $product['description']
            ];

            $response = $this->client->put($this->baseUrl . '/companies/' . $this->compnaySlug . '/products/' . $number, [
                'headers' => $this->fikenHttpHeader,
                'body' => json_encode($body),
            ]);

            $response =  $this->client->request('GET', $response->getHeaders()['Location'][0], [
                'headers' => $this->fikenHttpHeader,
            ]);
            $companieProduct = json_decode($response->getBody()->getContents(), true);

            return $companieProduct;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    // get company bank information
    public function getAccounts()
    {
        try {
            $response =  $this->client->request('GET', $this->baseUrl . '/companies/' . $this->compnaySlug . '/bankAccounts', [
                'headers' => $this->fikenHttpHeader,
            ]);

            $companieInvoice = json_decode($response->getBody()->getContents(), true);

            return $companieInvoice;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    // create contact
    public function createContact($data, $organizationNumber)
    {
        try {
            // $addressC =  [
            //     "streetAddress" => "Karl Johan 34",
            //     "streetAddressLine2" => "H0405",
            //     "city" => "Oslo",
            //     "postCode" => "0550",
            //     "country" => "Norway"
            // ];

            // $contactPerson = [
            //     "name" => "Test2",
            //     "email" => "test2@gmail.com",
            //     "phoneNumber" => "98573564",
            //     "address" => $addressC
            // ];

            $address = [
                "streetAddress" => $data->address,
                // "streetAddressLine2" => "H0405",
                "city" => $data->city,
                "postCode" => $data->zone_code,
                "country" => $data->country,
            ];

            $body = [
                "name" => $data->name,
                "email" => $data->email,
                "organizationNumber" => $organizationNumber,
                "phoneNumber" => $data->phone_nubmer,
                // "memberNumber" => 5465,
                "customer" => true,
                "supplier" => false,
                // "bankAccountNumber" => "11112233334",
                "currency" => "NOK",
                "language" => "Norwegian",
                "language" => $data->phone_nubmer,
                "inactive" => false,
                "daysUntilInvoicingDueDate" => 15,
                "address" => $address,
                // "contactPerson" => ([$contactPerson])
            ];

            $response = $this->client->post($this->baseUrl . '/companies/' . $this->compnaySlug . '/contacts', [
                'headers' => $this->fikenHttpHeader,
                'body' => json_encode($body),
            ]);

            $companieInvoice =  $this->client->request('GET', $response->getHeaders()['Location'][0], [
                'headers' => $this->fikenHttpHeader,
            ]);
            $companieInvoice = json_decode($companieInvoice->getBody()->getContents(), true);

            return $companieInvoice;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }


    // create invoice
    public function createInvoice($contact, $product, $additionalProduct, $bank, $subscriptionData)
    {
        try {
            $line = [
                // "net" => 51750,
                // "vat" => '25',
                "vatType" => $product['vatType'],
                // "gross" => 5000,
                // "vatInPercent" => 20,
                "unitPrice" => $product['unitPrice'],
                "quantity" => $subscriptionData['quantity'],
                "discount" => @$subscriptionData['discount'],
                // "productName" => $product['name'],
                "productId" => $product['productId'],
                "description" => $product['name'],
                "comment" => $product['note'],
                "incomeAccount" => $product['incomeAccount'],
            ];

            $products = [$line];

            if ($additionalProduct) {
                $line2 = [
                    "vatType" => $additionalProduct['vatType'],
                    "unitPrice" => $additionalProduct['unitPrice'],
                    "quantity" => @$subscriptionData['additional_users'],
                    "productId" => $additionalProduct['productId'],
                    "description" => $additionalProduct['name'],
                    "comment" =>  $additionalProduct['note'],
                    "incomeAccount" => $additionalProduct['incomeAccount'],
                ];
                $products = [$line, $line2];
            }

            $body = [
                "issueDate" => "2023-08-23",
                "dueDate" => "2023-08-30",
                "customerId" => $contact['contactId'],
                // "contactPersonId" => '4937883182',
                "bankAccountCode" => $bank['accountCode'],
                "accountCode" => "1001",
                "accountNumber" => $bank['bankAccountNumber'],
                "currency" => "NOK",
                "invoiceText" => "Invoice for services rendered during the Oslo Knitting Festival.",
                "cash" => true,
                // "projectId" => 4767197856,
                "bankAccountNumber" => $bank['bankAccountNumber'],
                "paymentAccount" => $bank['accountCode'],
                "ourReference" => "",
                "yourReference" => "",
                "orderReference" => "#54618",
                "lines" => ($products),
            ];

            $createInvoice = $this->client->post($this->baseUrl . '/companies/' . $this->compnaySlug . '/invoices', [
                'headers' => $this->fikenHttpHeader,
                'body' => json_encode($body),
            ]);

            $response =  $this->client->request('GET', $createInvoice->getHeaders()['Location'][0], [
                'headers' => $this->fikenHttpHeader,
            ]);
            $companieInvoice = json_decode($response->getBody()->getContents(), true);

            return $companieInvoice;
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    public function getInvoicePdf($url)
    {
        try {
            $response =  $this->client->request('GET', $url, [
                'headers' => $this->fikenHttpHeader,
            ]);
            return ($response->getBody()->getContents());

        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }
}