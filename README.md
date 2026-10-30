<p align="center">
  <img src="docs/img/banner.svg" alt="DSK - Gestão de Custo" width="100%">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL 8">
  <img src="https://img.shields.io/badge/Bootstrap-4.6-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap 4.6">
  <img src="https://img.shields.io/badge/JavaScript-ES5-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/OpenStreetMap-Mapas-7EBC6F?style=for-the-badge&logo=openstreetmap&logoColor=white" alt="OpenStreetMap">
  <img src="https://img.shields.io/badge/Leaflet-1.9.4-199900?style=for-the-badge&logo=leaflet&logoColor=white" alt="Leaflet">
</p>

<p align="center">
  <b>Plataforma web para motoristas parceiros controlarem rotas, custos e lucro de cada entrega.</b>
</p>

---

## 📌 Sobre o projeto

O **DSK - Gestão de Custo** ajuda o motorista parceiro a saber, rota por rota, **quanto vai receber, quanto gastou e quanto sobrou**. O cliente cadastra o trajeto, a distância é calculada automaticamente pelo mapa, os custos são lançados por trecho e o sistema mostra o lucro real. O administrador acompanha os clientes e controla a mensalidade da plataforma.

## 📑 Sumário

- [Funcionalidades](#-funcionalidades)
- [Demonstração](#-demonstração)
- [Cálculo automático de distância](#-cálculo-automático-de-distância)
- [Tecnologias](#-tecnologias)
- [Como executar (XAMPP)](#-como-executar-xampp)
- [Acesso inicial](#-acesso-inicial)
- [Administração](#-administração)
- [Estrutura do projeto](#-estrutura-do-projeto)
- [Segurança](#-segurança)

## ✨ Funcionalidades

| Área | O que faz |
| --- | --- |
| 🗺️ **Rotas** | Cadastro, edição, consulta e exclusão de trajetos, com origem, destino, data e valor por km. |
| 📍 **Distância automática** | Calcula os km pelas ruas reais e desenha o trajeto no mapa (OpenStreetMap). |
| 🔁 **Ida e volta** | Uma opção dobra a distância, os custos e o valor a receber. |
| ⛽ **Custos da rota** | Combustível calculado automaticamente pela distância, consumo (km/L) e preço por litro; demais custos continuam manuais. O lucro é calculado por rota. |
| 📊 **Dashboard e relatórios** | Visão geral dos resultados e download de planilha compatível com Excel, filtrada por dia, mês, ano ou intervalo personalizado. |
| 🗂️ **Histórico de rotas** | Filtre as rotas por dia, mês, ano ou intervalo personalizado. |
| 👤 **Dados pessoais** | O cliente mantém seu próprio cadastro atualizado. |
| 🛡️ **Painel administrativo** | Gestão de clientes (dados, senha e status da conta) e financeiro das mensalidades. |
| 🌗 **Tema claro e escuro** | Alternância de tema, lembrada pelo navegador. |

Em **Dados pessoais**, informe o consumo médio do veículo em km/L e o preço atual do combustível por litro. Ao selecionar **Combustível** nos custos da rota, o sistema calcula o gasto estimado com base nos quilômetros da rota; os outros tipos de custo continuam sendo preenchidos manualmente.

## 🎬 Demonstração

<p align="center">
  <img src="docs/img/rotas.png" alt="Tela de cadastro de rotas" width="90%">
  <br>
  <sub>Tela de cadastro de rotas do perfil cliente.</sub>
</p>

<!--
  GIFS DE DEMONSTRAÇÃO
  Grave a tela (ex.: ScreenToGif), salve em docs/img/ e remova os comentários abaixo:

<p align="center">
  <img src="docs/img/demo-distancia.gif" alt="Cálculo automático de distância e mapa" width="90%">
  <br><sub>Distância calculada automaticamente e trajeto no mapa.</sub>
</p>

<p align="center">
  <img src="docs/img/demo-custos.gif" alt="Lançamento de custos e lucro da rota" width="90%">
  <br><sub>Lançamento de custos e lucro da rota.</sub>
</p>
-->

## 📍 Cálculo automático de distância

Ao informar **origem** e **destino**, o sistema localiza os endereços, calcula a distância real por ruas e mostra o trajeto no mapa. O campo de km continua editável para ajustes manuais.

```mermaid
flowchart LR
    A[Origem e destino] --> B[Nominatim<br/>localiza os endereços]
    B --> C[OSRM<br/>calcula a rota por ruas]
    C --> D[Distância em km]
    C --> E[Leaflet<br/>desenha o mapa]
    D --> F[Valor a receber]
```

- O cálculo roda quando o motorista sai do campo, respeitando o limite de uso dos serviços gratuitos.
- Endereços sem cidade informada são procurados primeiro em **Joinville/SC**.
- A linha de status mostra o endereço encontrado, para conferência.
- Exige internet no navegador. Sem ela, os km podem ser digitados manualmente.

## 🧰 Tecnologias

**Back-end**
- PHP 8.2+ puro, sem framework
- PDO com MySQL 8 (consultas preparadas)
- Sessões nativas, hash de senha e proteção CSRF

**Front-end**
- HTML, CSS próprio (variáveis CSS, tema claro e escuro)
- Bootstrap 4.6.2, jQuery 3.6.0 e Bootstrap Icons 1.11.3
- JavaScript puro para as interações e cálculos
- Fontes Sora, Inter e JetBrains Mono

**Mapas**
- [OpenStreetMap](https://www.openstreetmap.org) (mapa base)
- [Nominatim](https://nominatim.org) (busca de endereços)
- [OSRM](https://project-osrm.org) (rotas e distâncias)
- [Leaflet](https://leafletjs.com) (exibição do mapa)

## 🚀 Como executar (XAMPP)

**Requisitos:** XAMPP instalado (PHP 8.2+ e MySQL) e um navegador com internet.

1. Abra o **XAMPP Control Panel**.
2. Clique em **Start** em **Apache** e em **MySQL**.
3. Abra o **CMD**, entre na pasta do projeto (`dsk`) e execute:

   ```bash
   C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public
   ```

   Mantenha essa janela aberta enquanto usa o sistema.
4. Acesse **http://127.0.0.1:8000**.

### Primeira vez: criar o banco de dados

1. Com o MySQL iniciado, acesse **http://localhost/phpmyadmin** (ou use o MySQL Workbench).
2. Importe/execute somente o arquivo `db/init.sql`.

> ⚠️ O script apaga e recria **apenas** o banco `projetoindividual`, com todas as tabelas, o usuário do banco `dsk` e a conta de administrador. Outros bancos não são alterados, e os dados de teste desse banco são apagados.

Se os dados de conexão forem diferentes, defina `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` no ambiente do PHP. Padrões: `127.0.0.1`, `projetoindividual`, `dsk` e `dsk123`.

## 🔑 Acesso inicial

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Administrador | `eu sei dsk.danillo` | `1~~@~~1` |

Este é o único e-mail autorizado a usar o perfil de administrador. A senha fica armazenada no banco como hash, e o usuário MySQL `dsk` é separado do login da aplicação. Clientes se cadastram pela própria aplicação.

> 🔒 Credenciais acima são apenas para uso local e demonstração. **Altere-as antes de publicar o sistema em um servidor real.**

## 🛠️ Administração

O menu do administrador tem **Dashboard**, **Clientes** e **Financeiro**.

- **Clientes:** selecione um cadastro para editar dados pessoais e de acesso, redefinir a senha e controlar o status da conta. Cidade é salva no formato `CIDADE / UF`; e-mail e telefone não podem se repetir.
- **Financeiro:** controla somente a **mensalidade** que o cliente paga para usar a plataforma. Novos cadastros recebem o vencimento no dia em que foram criados e a primeira cobrança é lançada na hora. As próximas mensalidades são geradas automaticamente conforme o dia individual de vencimento; o administrador também pode gerar para todos ou somente para um cliente. A cobrança aparece no financeiro e no painel do cliente. O administrador registra a baixa quando confirma o recebimento. Pix é uma forma de pagamento registrada, não uma chave armazenada.
- O sistema **não processa pagamentos** nem recebe valores de rotas.

### Ativar a geração automática no Windows (XAMPP)

A geração recorrente depende do Agendador de Tarefas do Windows e do MySQL estar ligado. Crie uma tarefa diária, por exemplo às 08:00:

1. Abra **Agendador de Tarefas** e escolha **Criar Tarefa Básica**.
2. Selecione o gatilho **Diariamente**.
3. Como ação, selecione **Iniciar um programa**.
4. Em **Programa/script**, informe `C:\xampp\php\php.exe`.
5. Em **Adicionar argumentos**, informe `"C:\xampp\htdocs\projeto individual 3.0\dsk\scripts\gerar_mensalidades.php"` (com aspas e ajustado para a pasta onde o projeto está instalado).
6. Salve a tarefa e mantenha o MySQL do XAMPP ligado no horário escolhido. Se o computador ficar desligado, habilite a opção para executar a tarefa assim que possível após o horário agendado.

O script é idempotente: pode rodar diariamente sem duplicar mensalidades. Ele cria as cobranças do mês cujo vencimento individual já chegou. Para clientes antigos, ajuste o valor e o dia de vencimento em **Clientes → editar cliente**. A ação manual fica em **Financeiro → Gerar mensalidades**, onde é possível selecionar um único cliente.

## 🗂️ Estrutura do projeto

```text
dsk/
├── app/                  # bootstrap, conexão, autenticação e layout
│   └── lib/
├── db/
│   └── init.sql          # schema do banco de dados
├── scripts/
│   └── gerar_mensalidades.php # tarefa recorrente de geração
├── docs/
│   └── img/              # banner, prints e GIFs do README
└── public/               # document root do servidor
    ├── admin/            # dashboard, clientes e financeiro
    ├── cliente/          # dashboard, dados pessoais, rotas e custos
    ├── assets/           # css, js e imagens
    ├── cadastro.php
    ├── index.php         # login
    └── logout.php
```

**Tabelas do banco:** `usuarios`, `clientes`, `rotas`, `custos_rota` e `mensalidades`.

## 🛡️ Segurança

- Consultas preparadas (PDO) contra SQL injection
- Senhas com `password_hash` / `password_verify`
- Token CSRF em todos os formulários
- Regeneração do ID de sessão no login
- Acesso separado por perfil (administrador e cliente)

---

<p align="center">
  <sub>DSK - Gestão de Custo · Mapas © colaboradores do <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a></sub>
</p>
