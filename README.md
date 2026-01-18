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
- **Organização de Código**:
    - **Data Transfer Objects (DTOs)**: Utilizados para transitar dados entre o Controller e a camada de Serviço, garantindo tipagem forte e validação precoce.
    - **Repository Pattern (DIP)**: Implementação de Interfaces e Repositórios (`UserRepositoryInterface`, `WalletRepositoryInterface`) para isolar as regras de domínio e abstrair a persistência de dados.
    - **Service Layer**: Onde reside a lógica de negócio orquestrada de forma desacoplada.
- **Segurança e Precisão Financeira**:
    - **UUIDs**: Utilizados como chaves primárias em vez de IDs sequenciais, aumentando a segurança e facilitando a distribuição de dados.
    - **Tipos Decimais (BCMath)**: Todos os cálculos financeiros são realizados com precisão arbitrária (strings), evitando os erros de arredondamento comuns ao tipo `float`.
- **Atomicidade e Integridade (ACID)**:
    - **Transações Atômicas**: Transferências são protegidas por `DB::transaction`, garantindo que a operação seja revertida integralmente em caso de qualquer falha.
    - **Otimização de Performance**: Ambas as carteiras envolvidas na transferência são buscadas em uma **única query** utilizando `whereIn` com `lockForUpdate`, reduzindo a latência e garantindo consistência em cenários concorrentes.
    - **Notificações Resilientes**: O despacho de notificações ocorre apenas após o sucesso do commit da transação (`DB::afterCommit`), evitando envios indevidos em caso de rollback.
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
- ✅ **Clean Architecture & SOLID**: Código desacoplado e manutenível através de interfaces e injeção de dependência.
- ✅ **Resiliência em Notificações**: Uso de Filas com estratégia de *Retry* e *Exponential Backoff*.
- ✅ **Validadores Robustos**: Tratamento centralizado de erros e validações de entrada rigorosas via FormRequests e DTOs.
- ✅ **CI/CD Ready**: Estrutura preparada para automação de testes e deploys.

---

## 🔮 Propostas de Melhoria na Arquitetura

Visando a evolução do projeto para um cenário de alta volumetria e produção (Go-to-Market), sugiro os seguintes passos:

1.  **Idempotência em Transações**:
    -   **Cenário**: Evitar duplicidade de transferências em casos de instabilidade de rede (retries do cliente).
    -   **Solução**: Implementar middleware que valida o header `x-idempotency-key` via Redis antes de processar o débito.

2.  **Observabilidade (Tracing Distribuído)**:
    -   **Cenário**: Dificuldade de rastrear falhas em processos assíncronos (Fila/Worker).
    -   **Solução**: Integrar OpenTelemetry para monitorar o trace da requisição desde a API até o consumo do Job pelo Worker.

3.  **Segurança (Autenticação)**:
    -   **Solução**: Implementar autenticação JWT (OAuth2) via Keycloak ou Laravel Passport, garantindo que apenas o dono da carteira autorize o débito.

4.  **Auditoria (Ledger)**:
    -   **Solução**: Implementar uma tabela de *Ledger* (Livro-Razão) imutável (Append-Only) para registrar o histórico de movimentações, facilitando a conciliação financeira e auditoria.

---
Desenvolvido como projeto técnico.
