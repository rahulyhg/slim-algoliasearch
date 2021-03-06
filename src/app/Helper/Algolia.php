<?php

namespace App\Helper;

use AlgoliaSearch\Client;

class Algolia
{
    /** @var string  */
    protected $applicationId;

    /** @var string  */
    protected $apiKey;

    /** @var string  */
    protected $indexName;

    /** @var Client */
    protected $client;

    /** @var array  */
    protected $appMandatoryFields = [
        'name',
        'link',
        'category'
    ];

    /**
     * Algolia Helper constructor.
     *
     **/
    public function __construct()
    {
        $settings = Config::getSettings();
        $this->applicationId = $settings['algolia']['app_id'];
        $this->apiKey        = $settings['algolia']['api_key'];
        $this->indexName     = $settings['algolia']['index_name'];

        $this->connectToApi();
    }

    /**
     * Connect to Algolia API
     *
     * @return void
     * @throws \Exception
     */
    public function connectToApi()
    {
        if ( !$this->applicationId || !$this->apiKey) {
            throw new \Exception('Missing Algolia settings to connect');
        }

        $this->client = new Client($this->applicationId, $this->apiKey);
    }

    /**
     * Call PHP client to add an application
     *
     * @param array $data
     *
     * @return array $result
     * @throws \Exception
     */
    public function addApp($data)
    {
        $validatedData = $this->validateAppData($data);
        if (count($validatedData['errors']) > 0) {
            throw new \Exception(implode($validatedData['errors'], '<br/>'));
        }

        return $this->proceedAppCreation($validatedData['data']);
    }

    /**
     * Call PHP client to add an application from API
     *
     * @param array $data
     *
     * @return array $result
     * @throws \Exception
     */
    public function addAppFromApi($data)
    {
        $validatedData = $this->validateAppData($data);
        if (count($validatedData['errors']) > 0) {
           return ['errors' =>  implode($validatedData['errors'], '\n')];
        }

        return $this->proceedAppCreation($validatedData['data']);
    }

    /**
     * Proceed application creation
     *
     * @param array $data
     *
     * @return array $result
     */
    protected function proceedAppCreation($data)
    {
        $index = $this->client->initIndex($this->indexName);
        $result = $index->addObject($data);

        return $result;
    }

    /**
     * Application data validation
     *
     * @param array $data
     *
     * @return array
     */
    public function validateAppData($data)
    {
        $missingFields = [];
        $errors        = [];
        foreach ($this->appMandatoryFields as $field) {
            if(! isset($data[$field]) || $data[$field] == '') {
                $missingFields[] = $field;
            }
        }

        if (count($missingFields) > 0) {
            $errors[] = 'Missing field(s) : ' . implode($missingFields, ', ');
        }

        if (isset($data['rank']) && $data['rank'] != '') {
            if ( !is_numeric($data['rank'])) {
                $errors[] = 'Rank attribute must be numeric';
            }
            $data['rank'] = (int) $data['rank'];
        }

        return [
            'data'   => $data,
            'errors' => $errors
        ];
    }

    /**
     * Call PHP client to delete an application
     *
     * @param int $id
     *
     * @return void
     */
    public function deleteApp($id)
    {
        $index = $this->client->initIndex($this->indexName);
        $index->deleteObject((int) $id);
    }

    /**
     * Call PHP client to get index categories
     *
     * @return array
     */
    public function getIndexCategories()
    {
        $categories = [];
        $index = $this->client->initIndex($this->indexName);
        $result = $index->search('', [
            'facets' => ['category']
        ]);

        if (isset($result['facets']['category'])) {
            $categories = array_keys($result['facets']['category']);
        }
        return $categories;
    }

    /**
     * Call PHP client get applications by name
     *
     * @param string $name
     *
     * @return array
     */
    public function searchAppsByName($name)
    {
        $apps = [];
        $index = $this->client->initIndex($this->indexName);
        $result = $index->search($name);

        if (isset($result['hits'])) {
            $apps = $result['hits'];
        }

        return $apps;
    }
}
