# PATCH-002 — Health Panel Administrativo

Produto: MobilizaPro Enterprise Workforce Platform  
Versão base: 1.10 LTS  
Branch alvo: feature/stability-mysql-first

## Objetivo

Adicionar uma tela administrativa de diagnóstico técnico, usando o Enterprise Core criado no PATCH-001.

## Arquivos adicionados

- api/health-panel.php
- MANIFESTO_PATCH_002.md
- ROLLBACK_PATCH_002.md

## Arquivos alterados

Nenhum arquivo existente foi alterado.

## Banco de dados

Nenhuma alteração.

## Visual operacional

Nenhuma alteração nas telas do sistema.  
Apenas uma nova tela técnica administrativa foi adicionada.

## Teste

1. Estar logado como usuário Gerencial/Admin.
2. Acessar:

```txt
https://mobilizapro.com/api/health-panel.php
```

Resultado esperado:

- Banco conectado
- PHP OK
- Sessão OK
- Logs OK ou aviso de permissão
- Memória e tempo de resposta exibidos

## Rollback

Remover os arquivos adicionados neste patch.
