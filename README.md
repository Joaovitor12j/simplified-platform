# Pagamento Simplificado - Desafio Back-end

[![PHP Version](https://img.shields.io/badge/PHP-8.3-777bb4.svg?style=flat-square&logo=php)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-11-ff2d20.svg?style=flat-square&logo=laravel)](https://laravel.com/)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ed.svg?style=flat-square&logo=docker)](https://www.docker.com/)
[![Build Status](https://img.shields.io/badge/Build-Passing-brightgreen.svg?style=flat-square)](#)

## 📌 Sobre o Projeto

Este projeto é uma implementação de uma API RESTful para a simulação de uma plataforma de pagamentos simplificada. A solução foi projetada com foco em **alta performance**, **consistência de dados** e **escalabilidade**.

### Diferenciais de Performance
- **Laravel Octane com Swoole**: A aplicação utiliza o servidor de alto desempenho Swoole, eliminando o overhead de inicialização do framework a cada requisição e mantendo o estado na memória para respostas ultra-rápidas.
- **Processamento Assíncrono**: O envio de notificações é delegado para filas gerenciadas pelo **Redis**, garantindo que a resposta ao usuário não seja bloqueada por serviços externos instáveis.

---

## 🏗️ Arquitetura e Decisões Técnicas

A arquitetura foi desenhada seguindo os princípios de **Clean Architecture** e **SOLID**, garantindo que a lógica de negócio esteja desacoplada de detalhes de infraestrutura.

- **Stack Tecnológica**: PHP 8.3+, Laravel 11+, Laravel Octane (Swoole), PostgreSQL, Redis e Docker.
- **Organização de Código**: Implementação de *Services* e *Repositories* para isolar as regras de domínio e abstrair a persistência de dados.
- **Segurança e Precisão Financeira**:
    - **UUIDs**: Utilizados como chaves primárias em vez de IDs sequenciais, aumentando a segurança e facilitando a distribuição de dados.
    - **Tipos Decimais (BCMath)**: Todos os cálculos financeiros são realizados com precisão arbitrária (strings), evitando os erros de arredondamento comuns ao tipo `float`.
- **Atomicidade e Integridade (ACID)**: Transferências são protegidas por transações de banco de dados (`DB Transactions`), garantindo que a operação seja revertida integralmente em caso de qualquer falha.
- **Fail Fast**: Validações robustas via *Form Requests* e exceções de domínio customizadas identificam falhas antes do processamento pesado.

---

## 🚀 Instalação e Execução

A aplicação é totalmente dockerizada para facilitar o desenvolvimento e deploy.

### Pré-requisitos
- [Docker](https://www.docker.com/) e [Docker Compose](https://docs.docker.com/compose/) instalados.

### Start Rápido
Utilize o `Makefile` incluído para automatizar a configuração inicial:

1. **Setup Completo**:
   ```bash
   make setup
   ```
   *(Este comando cria o .env, sobe os containers, instala dependências e executa migrations com seeds)*

2. **Subir a aplicação**:
   ```bash
   make up
   ```

3. **Acompanhar Logs**:
   ```bash
   make logs
   ```

### Outros comandos úteis:
- `make test`: Executa a suíte completa de testes (Pest/PHPUnit).
- `make down`: Encerra todos os serviços.
- `make reload`: Reinicia o worker do Octane (aplicação).

---

## 📖 Documentação da API

### Efetuar Transferência
`POST /transfer`

Realiza a transferência de valores entre usuários comuns e de usuários comuns para lojistas.

**Exemplo de Request:**
```json
{
  "value": 100.50,
  "payer": "550e8400-e29b-41d4-a716-446655440000",
  "payee": "660f9511-f30c-52e5-b827-557766551111"
}
```

**Resposta de Sucesso (201 Created):**
```json
{
  "id": "770g0622-g41d-63f6-c938-668877662222",
  "payer_wallet_id": "...",
  "payee_wallet_id": "...",
  "amount": "100.50",
  "created_at": "2026-01-17T16:19:00.000000Z"
}
```

**Resposta de Erro (Ex: Saldo Insuficiente - 422/400):**
```json
{
  "message": "Saldo insuficiente para realizar a transferência."
}
```

> **Fluxo Interno**: Validação -> Consulta Autorizador Externo -> Transação Bancária -> Disparo de Notificação (Async via Redis).

---

## 🧪 Como Rodar os Testes

A aplicação possui testes de unidade e integração que garantem a confiabilidade das regras de negócio.

```bash
make test
```

Os cenários testados incluem:
- Transferência bem-sucedida entre usuários.
- Impedimento de transferência iniciada por lojista.
- Validação de saldo insuficiente.
- Tratamento de falhas no serviço autorizador.

---

## ✨ Diferenciais Implementados

- ✅ **Dockerização Modular**: Containers separados para App (Swoole), DB, Redis e Queue Worker.
- ✅ **Resiliência em Notificações**: Uso de Filas com estratégia de *Retry* e *Exponential Backoff*.
- ✅ **Validadores Robustos**: Tratamento centralizado de erros e validações de entrada rigorosas.
- ✅ **CI/CD Ready**: Estrutura preparada para automação de testes e deploys.

---
Desenvolvido como projeto técnico.
