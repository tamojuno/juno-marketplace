<?php
namespace DigitalHub\Juno\Model\Config\Source;

class CustomerAttributes implements \Magento\Framework\Option\ArrayInterface
{
    private $attributeCollectionFactory;

    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $attributeCollectionFactory
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['label' => '', 'value' => ''];

        $model = $this->attributeCollectionFactory->create();
        $model->setEntityTypeFilter(1);

        foreach ($model->getData() as $item) {
            if ($item['frontend_label']) {
                $options[] = [
                    'label' => __($item['frontend_label']) . ' (Cliente)',
                    'value' => 'customer_' . $item['attribute_code']
                ];
            }
        }

        $options[] = ['label' => 'Rua Linha 1 (Endereço)', 'value' => 'address_street_1'];
        $options[] = ['label' => 'Rua Linha 2 (Endereço)', 'value' => 'address_street_2'];
        $options[] = ['label' => 'Rua Linha 3 (Endereço)', 'value' => 'address_street_3'];
        $options[] = ['label' => 'Rua Linha 4 (Endereço)', 'value' => 'address_street_4'];

        $model = $this->attributeCollectionFactory->create();
        $model->setEntityTypeFilter(2);

        foreach ($model->getData() as $item) {
            if ($item['frontend_label'] && $item['attribute_code'] != 'street') {
                $options[] = [
                    'label' => __($item['frontend_label']) . ' (Endereço)',
                    'value' => 'address_' . $item['attribute_code']
                ];
            }
        }

        return $options;
    }
}
