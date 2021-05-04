# Módulo Juno para Magento v2

### Descrição 
---------------
Adicione e configure o módulo para oferecer a Juno como opção de pagamento dentro de sua loja Magento. 

O módulo foi desenvolvido com base na nossa **API 1.0**. Você pode acessar a documentação base abaixo: 

https://dev.juno.com.br/api/v1

## Requisitos

  - Versão mínima para o Magento: 2.3.0
  - Versão mínima para 7.1.3  

## Importante

Para utilizar e configurar o módulo você precisa ter um **cadastro completo** na Juno. 

Você pode fazer seu cadastro clicando [aqui](https://app.juno.com.br/).

## Instalação 

- Vá até o diretório raíz do seu projeto magento e execute os passos abaixo.

`composer require juno/magento2:dev-master`

Caso seja a primeira vez instalando um componente magento, suas credenciais serão solicitadas. Para entender como e onde preenchê-las, utilize os steps da [documentação oficial](http://devdocs.magento.com/guides/v2.0/install-gde/prereq/connect-auth.html) do Magento em caso de dúvidas. 

- Para ter certeza de que o módulo irá aparecer no painel admin, não esqueça de atualizar o projeto

  `php bin/magento setup:upgrade`
  `php bin/magento setup:static-content:deploy`

- Caso sua loja esteja com tradução pt_BR: 
  
  `php bin/magento setup:upgrade`
  `php bin/magento setup:static-content:deploy pt_BR `

### Homologação

Para testes, possuímos um ambiente específico e dedicado, onde você pode fazer todas as validações necessárias antes de virar a chave para começar a processar. 

Por ser um ambiente à parte do ambiente de produção você precisa fazer um cadastro nesse ambiente caso deseje **executar testes** e **homologar sua integração com segurança**. 
