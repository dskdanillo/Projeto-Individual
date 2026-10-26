# DSK - Gestao de Custo

## Requisitos
- PHP 8.2 ou superior com as extensoes `pdo` e `pdo_mysql` habilitadas.
- MySQL 8.0.16 ou superior instalado e em execucao localmente (para aplicar a restricao CHECK do administrador principal).

## Execucao local
1. Abra somente `db/init.sql` no MySQL Workbench 8.0 CE conectado ao MySQL local com uma conta administradora.
2. Execute o arquivo inteiro. Ele apaga e recria **apenas** o banco `projetoindividual`, instala todas as tabelas e cria a conta admin `dsk.danillo@gmail.com`. Outros bancos do MySQL não são alterados. Os dados de teste atuais desse banco serão apagados. Não precisa apagar o banco manualmente antes.
3. `db/init.sql` é o único script SQL do projeto. Ele cria o banco sem clientes de demonstração; clientes podem se cadastrar pela aplicação. Se já executou uma versão anterior que criou `pix_chave`, confira com `SHOW COLUMNS FROM projetoindividual.clientes LIKE 'pix_chave';` e, se a coluna existir, remova-a com `ALTER TABLE projetoindividual.clientes DROP COLUMN pix_chave;`.
4. Na pasta do projeto, inicie o servidor PHP: `php -S 127.0.0.1:8000 -t public`
5. Abra http://127.0.0.1:8000 e entre como administrador. O banco novo ja vem com a conta indicada abaixo.

Se os dados de conexao forem diferentes, configure `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` no ambiente do PHP. Os valores padrao sao `127.0.0.1`, `projetoindividual`, `dsk` e `dsk123`.

## Acesso inicial
- Administrador: `dsk.danillo@gmail.com` / `12345@54321`
- Este é o único e-mail autorizado a usar o perfil de administrador.

A senha do administrador e armazenada no banco como hash. O usuario MySQL `dsk` / `dsk123` e separado desse login da aplicacao.

## Rotas
Informe manualmente a distância de ida e os custos por trecho. A opção “Incluir retorno” dobra a distância, os custos e o valor a receber. Rotas podem ser cadastradas, consultadas, editadas e excluídas sem integração com mapas ou APIs externas.

## Administracao
O menu do administrador tem as paginas Dashboard, Clientes e Financeiro. Na pagina Clientes, selecione um cadastro para editar dados pessoais e de acesso, redefinir a senha e controlar o status da conta. Valores de mensalidade e cobranças ficam somente na pagina Financeiro.

No cadastro de clientes, a cidade e salva em maiusculas no formato `CIDADE / UF`; e-mail e telefone nao podem se repetir. Depois de entrar, o cliente pode abrir `Dados pessoais` no menu para atualizar seus dados de cadastro.

O Financeiro controla somente a mensalidade que o cliente paga para usar a plataforma: o administrador gera a cobranca do mes e registra a baixa quando confirmar o recebimento. O sistema nao processa pagamentos nem recebe valores de rotas. Pix continua como uma opcao para registrar a forma de pagamento da mensalidade, nao como uma chave Pix armazenada no cadastro do cliente.

## Estrutura
- `public/` - paginas acessiveis (document root do servidor PHP)
- `app/` - bootstrap, conexao, autenticacao e layout
- `db/init.sql` - schema inicial do banco de dados
