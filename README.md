# API de gerenciamento de contatos

Esta é uma API simples construída com CodeIgniter 4 para o gerenciamento de contatos.

## Tecnologias utilizadas

- CodeIgniter4 (Framework)
- PHP 8.0
- MySQL

## Instalação do projeto

Para fazer a instalação do projeto, primeiro clone o repositório para sua máquina.

```bash
  git clone https://github.com/gabrielmachado04/teste-api-ci4-pleno
```

Após este primeiro passo, configure os arquivos essenciais como nome do banco de dados e credenciais.

Em seguida, execute os comandos para geração dos bancos e fazer a primeira população de dados para a execução dos testes.

```bash
  php spark migrate
  php spark db:seed Contacts
```

Para a execução, daremos exemplos de como utilizar o projeto utilizando o XAMPP.

- Inicialize o servidor Apache, assim como o MySQL.
- Mova o projeto para a pasta htdocs, normalmente localizada em: C:\xampp\htdocs

Após a incialização e o projeto no local correto, apenas execute os testes da API utilizando a sua ferramenta de preferência, por exemplo Postman.

Em nossa documentação é possível importar todas as requests e parâmetros para o Postman e efetuar um teste completo dos endpoints e retornos do projeto.
## Documentação
Segue a documentação detalhada com todas as requests, parâmetros e métodos de exemplo para a execução da API.

Lá são mostrados alguns exemplos incluindo os parâmetros obrigatórios, validações, assim como retornos esperados pela API.

[Documenter](https://documenter.getpostman.com/view/18096746/2sB2iwHFBy)

