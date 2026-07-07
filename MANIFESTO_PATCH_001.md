# PATCH 001 — Enterprise Core

Produto: MobilizaPro Enterprise Workforce Platform  
Versão base: 1.10 LTS  
Branch alvo: feature/stability-mysql-first

## Objetivo

Adicionar a primeira camada Enterprise sem alterar o comportamento atual do sistema.

## Arquivos novos

- api/Core/Database.php
- api/Core/Logger.php
- api/Core/Response.php
- api/health.php

## Arquivos alterados

Nenhum arquivo existente foi alterado neste patch.

## Banco de dados

Nenhuma alteração de banco.

## Visual

Nenhuma alteração visual.

## Risco

Baixo.

## Como testar

1. Copiar os arquivos para a branch feature/stability-mysql-first.
2. Fazer commit.
3. Subir em ambiente de teste.
4. Logar com usuário Gerencial/Admin.
5. Acessar:

```txt
https://mobilizapro.com/api/health.php
```

Resultado esperado:

- database OK
- PHP OK
- session OK
- user OK

## Rollback

Remover os arquivos adicionados:

- api/Core/Database.php
- api/Core/Logger.php
- api/Core/Response.php
- api/health.php
