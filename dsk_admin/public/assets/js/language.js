(function () {
  var KEY = 'dsk_language';
  var translations = [
    ['Idioma', 'Language', 'Idioma'],
    ['Tema noite', 'Dark theme', 'Tema oscuro'],
    ['Tema dia', 'Light theme', 'Tema claro'],
    ['DSK - Gestão de Custo', 'DSK - Cost Management', 'DSK - Gestión de Costos'],
    ['Gestão de Custo', 'Cost Management', 'Gestión de Costos'],
    ['Controle de rotas, custos e lucro', 'Track routes, costs, and profit', 'Control de rutas, gastos y ganancias'],
    ['Entre com seu e-mail e senha.', 'Sign in with your email and password.', 'Inicia sesión con tu correo electrónico y contraseña.'],
    ['Entrar', 'Sign in', 'Iniciar sesión'],
    ['Criar conta de motorista', 'Create driver account', 'Crear cuenta de conductor'],
    ['Voltar para o login', 'Back to sign in', 'Volver al inicio de sesión'],
    ['Senha', 'Password', 'Contraseña'],
    ['Confirme sua senha', 'Confirm your password', 'Confirma tu contraseña'],
    ['Mostrar senha', 'Show password', 'Mostrar contraseña'],
    ['Ocultar senha', 'Hide password', 'Ocultar contraseña'],
    ['E-mail ou senha inválidos.', 'Invalid email or password.', 'Correo electrónico o contraseña incorrectos.'],
    ['Esta conta está inativa.', 'This account is inactive.', 'Esta cuenta está inactiva.'],
    ['Esta conta não está autorizada como administrador.', 'This account is not authorized as an administrator.', 'Esta cuenta no está autorizada como administrador.'],
    ['Menu', 'Menu', 'Menú'],
    ['Dashboard', 'Dashboard', 'Panel'],
    ['Clientes', 'Clients', 'Clientes'],
    ['Financeiro', 'Finances', 'Finanzas'],
    ['Dados pessoais', 'Personal details', 'Datos personales'],
    ['Rotas', 'Routes', 'Rutas'],
    ['Custos da rota', 'Route expenses', 'Gastos de ruta'],
    ['Administrador', 'Administrator', 'Administrador'],
    ['Motorista parceiro', 'Partner driver', 'Conductor asociado'],
    ['Sair', 'Sign out', 'Cerrar sesión'],
    ['Início do cliente', 'Customer home', 'Inicio del cliente'],
    ['Voltar à página anterior', 'Go back', 'Volver a la página anterior'],
    ['Painel administrativo', 'Admin dashboard', 'Panel de administración'],
    ['Visão geral dos clientes da plataforma', 'Overview of platform clients', 'Resumen de los clientes de la plataforma'],
    ['Baixar relatório PDF', 'Download PDF report', 'Descargar informe PDF'],
    ['Clientes cadastrados', 'Registered clients', 'Clientes registrados'],
    ['Contas ativas', 'Active accounts', 'Cuentas activas'],
    ['Inadimplentes (offline)', 'Overdue (offline)', 'Morosos (sin acceso)'],
    ['Contas inativas', 'Inactive accounts', 'Cuentas inactivas'],
    ['Novos clientes em', 'New clients in', 'Nuevos clientes en'],
    ['Rotas cadastradas na plataforma', 'Routes registered on the platform', 'Rutas registradas en la plataforma'],
    ['Últimos cadastros', 'Recent registrations', 'Registros recientes'],
    ['Cliente', 'Client', 'Cliente'],
    ['Cidade', 'City', 'Ciudad'],
    ['Cadastro', 'Registration', 'Registro'],
    ['Conta', 'Account', 'Cuenta'],
    ['Nenhum cliente cadastrado.', 'No clients registered.', 'No hay clientes registrados.'],
    ['Editar cliente', 'Edit client', 'Editar cliente'],
    ['Novo cliente', 'New client', 'Nuevo cliente'],
    ['Cadastre um cliente manualmente', 'Add a client manually', 'Registra un cliente manualmente'],
    ['Dados do cliente', 'Client details', 'Datos del cliente'],
    ['Nome', 'Name', 'Nombre'],
    ['Nome completo', 'Full name', 'Nombre completo'],
    ['E-mail (login)', 'Email (login)', 'Correo electrónico (acceso)'],
    ['E-mail de acesso', 'Login email', 'Correo electrónico de acceso'],
    ['Telefone', 'Phone', 'Teléfono'],
    ['Telefone / WhatsApp', 'Phone / WhatsApp', 'Teléfono / WhatsApp'],
    ['Cidade / UF', 'City / State', 'Ciudad / Estado'],
    ['Cidade/UF', 'City/State', 'Ciudad/Estado'],
    ['Tipo de veículo', 'Vehicle type', 'Tipo de vehículo'],
    ['Tipo', 'Type', 'Tipo'],
    ['Modelo do veículo', 'Vehicle model', 'Modelo del vehículo'],
    ['Modelo', 'Model', 'Modelo'],
    ['Placa', 'License plate', 'Matrícula'],
    ['Cobrança mensal', 'Monthly billing', 'Cobro mensual'],
    ['Valor mensal (R$)', 'Monthly amount (BRL)', 'Importe mensual (BRL)'],
    ['Dia de vencimento', 'Due day', 'Día de vencimiento'],
    ['Novos cadastros usam o dia em que a conta foi criada.', 'New accounts use the day they were created as the due date.', 'Las cuentas nuevas usan como vencimiento el día en que se crearon.'],
    ['Acesso do cliente (login e senha)', 'Client access (login and password)', 'Acceso del cliente (usuario y contraseña)'],
    ['Mínimo 6 caracteres', 'At least 6 characters', 'Mínimo 6 caracteres'],
    ['Deixe em branco para manter a atual', 'Leave blank to keep the current password', 'Déjalo en blanco para mantener la actual'],
    ['Gerar senha aleatória', 'Generate random password', 'Generar contraseña aleatoria'],
    ['Gerar', 'Generate', 'Generar'],
    ['Anote a senha para entregar ao cliente.', 'Write down the password to give to the client.', 'Anota la contraseña para entregársela al cliente.'],
    ['A senha atual não pode ser consultada (fica criptografada). Para redefinir, digite ou gere uma nova e informe ao cliente.', 'The current password cannot be viewed because it is encrypted. To reset it, enter or generate a new one and share it with the client.', 'La contraseña actual no se puede consultar porque está cifrada. Para restablecerla, escribe o genera una nueva e infórmasela al cliente.'],
    ['Informações do cliente', 'Client information', 'Información del cliente'],
    ['Salvar alterações', 'Save changes', 'Guardar cambios'],
    ['Situação da conta', 'Account status', 'Estado de la cuenta'],
    ['Atual:', 'Current:', 'Actual:'],
    ['Motivo:', 'Reason:', 'Motivo:'],
    ['Alterada em', 'Changed on', 'Cambiada el'],
    ['Motivo / observação', 'Reason / note', 'Motivo / observación'],
    ['Reativar conta', 'Reactivate account', 'Reactivar cuenta'],
    ['Manter offline (inadimplente)', 'Keep offline (overdue)', 'Mantener sin acceso (moroso)'],
    ['Desativar conta', 'Deactivate account', 'Desactivar cuenta'],
    ['Excluir conta', 'Delete account', 'Eliminar cuenta'],
    ['Excluir conta do cliente', 'Delete client account', 'Eliminar cuenta del cliente'],
    ['Novo cliente', 'New client', 'Nuevo cliente'],
    ['Cadastrar cliente', 'Register client', 'Registrar cliente'],
    ['Buscar', 'Search', 'Buscar'],
    ['Situação', 'Status', 'Estado'],
    ['Todas', 'All', 'Todas'],
    ['Todos', 'All', 'Todos'],
    ['Filtrar', 'Filter', 'Filtrar'],
    ['Filtrar histórico', 'Filter history', 'Filtrar historial'],
    ['Limpar', 'Clear', 'Limpiar'],
    ['Clique no nome do cliente para editar as informações, redefinir login/senha ou controlar o acesso.', 'Click a client name to edit their information, reset login/password, or manage access.', 'Haz clic en el nombre del cliente para editar su información, restablecer el acceso o administrar los permisos.'],
    ['Veículo', 'Vehicle', 'Vehículo'],
    ['Rotas', 'Routes', 'Rutas'],
    ['Nenhum cliente encontrado.', 'No clients found.', 'No se encontraron clientes.'],
    ['Editar', 'Edit', 'Editar'],
    ['Cancelar', 'Cancel', 'Cancelar'],
    ['Financeiro', 'Finances', 'Finanzas'],
    ['Mensalidades, pagamentos e inadimplência dos clientes', 'Client subscriptions, payments, and overdue accounts', 'Cuotas, pagos y morosidad de los clientes'],
    ['Gerar mensalidades', 'Generate subscriptions', 'Generar cuotas'],
    ['Nova cobrança', 'New charge', 'Nuevo cobro'],
    ['Previsto', 'Expected', 'Previsto'],
    ['Recebido', 'Received', 'Recibido'],
    ['A vencer', 'Due soon', 'Por vencer'],
    ['Em atraso', 'Overdue', 'Vencido'],
    ['todos os meses', 'all months', 'todos los meses'],
    ['Mês de referência', 'Reference month', 'Mes de referencia'],
    ['Vazio = todos os meses', 'Blank = all months', 'Vacío = todos los meses'],
    ['Pagas', 'Paid', 'Pagadas'],
    ['Voltar ao mês atual', 'Back to current month', 'Volver al mes actual'],
    ['Cobranças', 'Charges', 'Cobros'],
    ['Gere a mensalidade do mês e compartilhe os dados de pagamento com o cliente pelo canal habitual. Depois, registre aqui quando o pagamento for confirmado.', 'Generate this month’s subscription and share the payment details with the client. Then record the payment here once it is confirmed.', 'Genera la cuota del mes y comparte los datos de pago con el cliente. Después, registra aquí el pago cuando se confirme.'],
    ['Referência', 'Reference', 'Referencia'],
    ['Vencimento', 'Due date', 'Vencimiento'],
    ['Valor', 'Amount', 'Importe'],
    ['Pagamento', 'Payment', 'Pago'],
    ['Dar baixa', 'Mark as paid', 'Marcar como pagado'],
    ['Desfazer pagamento', 'Undo payment', 'Deshacer pago'],
    ['Excluir cobrança', 'Delete charge', 'Eliminar cobro'],
    ['Nenhuma cobrança encontrada.', 'No charges found.', 'No se encontraron cobros.'],
    ['Gere as mensalidades para elas aparecerem nesta lista e no painel do cliente.', 'Generate subscriptions for them to appear here and on the client dashboard.', 'Genera las cuotas para que aparezcan en esta lista y en el panel del cliente.'],
    ['Contas offline por inadimplência', 'Accounts offline due to overdue payments', 'Cuentas sin acceso por morosidad'],
    ['Em aberto', 'Outstanding', 'Pendiente'],
    ['Motivo', 'Reason', 'Motivo'],
    ['Nenhuma conta offline.', 'No offline accounts.', 'No hay cuentas sin acceso.'],
    ['Últimos 6 meses', 'Last 6 months', 'Últimos 6 meses'],
    ['Gerar', 'Generate', 'Generar'],
    ['Selecione um cliente para gerar somente a mensalidade dele.', 'Select a client to generate only their subscription.', 'Selecciona un cliente para generar únicamente su cuota.'],
    ['Todos os clientes elegíveis', 'All eligible clients', 'Todos los clientes elegibles'],
    ['Olá,', 'Hello,', 'Hola,'],
    ['Visão geral das rotas e custos', 'Overview of routes and costs', 'Resumen de rutas y gastos'],
    ['Todo o histórico', 'All time', 'Todo el historial'],
    ['Hoje', 'Today', 'Hoy'],
    ['Este mês', 'This month', 'Este mes'],
    ['Este ano', 'This year', 'Este año'],
    ['Receita das rotas', 'Route revenue', 'Ingresos de rutas'],
    ['Custos das rotas', 'Route expenses', 'Gastos de rutas'],
    ['Lucro líquido', 'Net profit', 'Ganancia neta'],
    ['rota(s) no período', 'route(s) in period', 'ruta(s) en el período'],
    ['Mensalidades da plataforma', 'Platform subscriptions', 'Cuotas de la plataforma'],
    ['Situação', 'Status', 'Estado'],
    ['Paga', 'Paid', 'Pagada'],
    ['Em atraso', 'Overdue', 'Vencida'],
    ['Nenhuma mensalidade registrada até o momento.', 'No subscriptions have been recorded yet.', 'Todavía no hay cuotas registradas.'],
    ['Gerar relatório', 'Generate report', 'Generar informe'],
    ['Baixe um PDF organizado com o resumo das rotas, faturamento, gastos por categoria e lucro no período escolhido.', 'Download a clear PDF summary of routes, revenue, expenses by category, and profit for the selected period.', 'Descarga un PDF organizado con el resumen de rutas, ingresos, gastos por categoría y ganancias del período seleccionado.'],
    ['Período', 'Period', 'Período'],
    ['Dia específico', 'Specific day', 'Día específico'],
    ['Mês', 'Month', 'Mes'],
    ['Ano', 'Year', 'Año'],
    ['Período personalizado', 'Custom period', 'Período personalizado'],
    ['Data', 'Date', 'Fecha'],
    ['Data inicial', 'Start date', 'Fecha de inicio'],
    ['Data final', 'End date', 'Fecha de fin'],
    ['Médias', 'Averages', 'Promedios'],
    ['Receita por km', 'Revenue per km', 'Ingresos por km'],
    ['Custo por km', 'Cost per km', 'Gasto por km'],
    ['Lucro por rota', 'Profit per route', 'Ganancia por ruta'],
    ['Margem', 'Margin', 'Margen'],
    ['Custos por categoria', 'Expenses by category', 'Gastos por categoría'],
    ['Nenhum custo lançado no período.', 'No expenses recorded for this period.', 'No hay gastos registrados en este período.'],
    ['Últimas rotas do período', 'Recent routes in this period', 'Rutas recientes del período'],
    ['Exibindo até 8 rotas mais recentes. Os totais acima consideram todas as rotas do período selecionado.', 'Showing up to 8 recent routes. Totals above include all routes in the selected period.', 'Se muestran hasta 8 rutas recientes. Los totales incluyen todas las rutas del período seleccionado.'],
    ['Rota', 'Route', 'Ruta'],
    ['Km', 'Km', 'Km'],
    ['Receita', 'Revenue', 'Ingresos'],
    ['Custo', 'Cost', 'Gasto'],
    ['Lucro', 'Profit', 'Ganancia'],
    ['Nenhuma rota no período.', 'No routes in this period.', 'No hay rutas en este período.'],
    ['Dados pessoais', 'Personal details', 'Datos personales'],
    ['Atualize suas informações de cadastro', 'Update your registration details', 'Actualiza tus datos de registro'],
    ['Cadastro pessoal', 'Personal information', 'Datos personales'],
    ['Use CIDADE / UF, por exemplo JOINVILLE / SC.', 'Use CITY / STATE, for example JOINVILLE / SC.', 'Usa CIUDAD / ESTADO, por ejemplo JOINVILLE / SC.'],
    ['Consumo do veículo (km/L)', 'Vehicle fuel economy (km/L)', 'Consumo del vehículo (km/L)'],
    ['Preço do combustível (R$/L)', 'Fuel price (BRL/L)', 'Precio del combustible (BRL/L)'],
    ['Informe os dois valores para que os custos de combustível sejam calculados automaticamente por rota. Deixe ambos em branco para não usar o cálculo automático.', 'Enter both values to automatically calculate fuel costs per route. Leave both blank to disable automatic calculation.', 'Introduce ambos valores para calcular automáticamente el combustible por ruta. Déjalos en blanco para desactivar el cálculo automático.'],
    ['Salvar dados', 'Save details', 'Guardar datos'],
    ['Nova rota', 'New route', 'Nueva ruta'],
    ['Editar rota', 'Edit route', 'Editar ruta'],
    ['De (origem)', 'From (origin)', 'Desde (origen)'],
    ['Para (destino)', 'To (destination)', 'Hasta (destino)'],
    ['Calcular distância', 'Calculate distance', 'Calcular distancia'],
    ['Preencha origem e destino para calcular a distância automaticamente (OpenStreetMap).', 'Enter an origin and destination to calculate the distance automatically (OpenStreetMap).', 'Introduce el origen y el destino para calcular la distancia automáticamente (OpenStreetMap).'],
    ['Incluir retorno', 'Include return trip', 'Incluir regreso'],
    ['Duplica a distância e o valor a receber.', 'Doubles the distance and amount to receive.', 'Duplica la distancia y el importe a recibir.'],
    ['Distância de ida (km)', 'One-way distance (km)', 'Distancia de ida (km)'],
    ['Valor por km (R$)', 'Rate per km (BRL)', 'Tarifa por km (BRL)'],
    ['Distância total', 'Total distance', 'Distancia total'],
    ['Você vai receber', 'You will receive', 'Vas a recibir'],
    ['Atualizar rota', 'Update route', 'Actualizar ruta'],
    ['Salvar rota', 'Save route', 'Guardar ruta'],
    ['Minhas rotas', 'My routes', 'Mis rutas'],
    ['Toque numa rota para lançar ou ver os custos dela.', 'Select a route to add or view its expenses.', 'Selecciona una ruta para agregar o consultar sus gastos.'],
    ['Filtrar histórico por', 'Filter history by', 'Filtrar historial por'],
    ['R$/km', 'BRL/km', 'BRL/km'],
    ['Receber', 'Receive', 'Recibir'],
    ['ida e volta', 'round trip', 'ida y vuelta'],
    ['Custos', 'Expenses', 'Gastos'],
    ['Nenhuma rota cadastrada ainda.', 'No routes have been registered yet.', 'Todavía no hay rutas registradas.'],
    ['Editar custo', 'Edit expense', 'Editar gasto'],
    ['Novo custo', 'New expense', 'Nuevo gasto'],
    ['Rota', 'Route', 'Ruta'],
    ['— Selecione uma rota —', '— Select a route —', '— Selecciona una ruta —'],
    ['A receber', 'To receive', 'Por recibir'],
    ['Custos desta rota', 'Expenses for this route', 'Gastos de esta ruta'],
    ['Lucro da rota', 'Route profit', 'Ganancia de la ruta'],
    ['Categoria', 'Category', 'Categoría'],
    ['Descrição', 'Description', 'Descripción'],
    ['Descrição (opcional)', 'Description (optional)', 'Descripción (opcional)'],
    ['Valor por trecho (R$)', 'Amount per leg (BRL)', 'Importe por tramo (BRL)'],
    ['Valor total', 'Total amount', 'Importe total'],
    ['Lançar custo', 'Add expense', 'Registrar gasto'],
    ['Custos desta rota', 'Expenses for this route', 'Gastos de esta ruta'],
    ['Todos os custos lançados', 'All recorded expenses', 'Todos los gastos registrados'],
    ['Nenhum custo lançado.', 'No expenses recorded.', 'No hay gastos registrados.'],
    ['Combustível', 'Fuel', 'Combustible'],
    ['Pedágio', 'Toll', 'Peaje'],
    ['Alimentação', 'Meals', 'Alimentación'],
    ['Manutenção', 'Maintenance', 'Mantenimiento'],
    ['Estacionamento', 'Parking', 'Estacionamiento'],
    ['Outros', 'Other', 'Otros'],
    ['Carro', 'Car', 'Coche'],
    ['Moto', 'Motorcycle', 'Motocicleta'],
    ['Van', 'Van', 'Furgoneta'],
    ['Utilitário', 'Utility vehicle', 'Vehículo utilitario'],
    ['Pix', 'Pix', 'Pix'],
    ['Boleto', 'Bank slip', 'Boleto bancario'],
    ['Cartão', 'Card', 'Tarjeta'],
    ['Transferência', 'Bank transfer', 'Transferencia'],
    ['Dinheiro', 'Cash', 'Efectivo'],
    ['A conta foi reativada: o cliente já pode entrar no sistema.', 'Account reactivated: the client can now sign in.', 'Cuenta reactivada: el cliente ya puede iniciar sesión.'],
    ['Conta reativada: o cliente já pode entrar no sistema.', 'Account reactivated: the client can now sign in.', 'Cuenta reactivada: el cliente ya puede iniciar sesión.'],
    ['Conta mantida offline por inadimplência até o acerto.', 'Account remains offline due to overdue payments until settled.', 'La cuenta permanece sin acceso por pagos pendientes hasta regularizarse.'],
    ['Conta desativada: o cliente não consegue mais entrar.', 'Account deactivated: the client can no longer sign in.', 'Cuenta desactivada: el cliente ya no puede iniciar sesión.'],
    ['Dados do cliente salvos.', 'Client details saved.', 'Datos del cliente guardados.'],
    ['Dados salvos e senha redefinida.', 'Details saved and password reset.', 'Datos guardados y contraseña restablecida.'],
    ['Cliente cadastrado e primeira mensalidade lançada.', 'Client registered and first subscription created.', 'Cliente registrado y primera cuota creada.'],
    ['Conta do cliente excluída.', 'Client account deleted.', 'Cuenta del cliente eliminada.'],
    ['Cliente não encontrado.', 'Client not found.', 'No se encontró el cliente.'],
    ['Já existe um cadastro com este e-mail.', 'An account with this email already exists.', 'Ya existe una cuenta con este correo electrónico.'],
    ['Este telefone já está cadastrado.', 'This phone number is already registered.', 'Este número de teléfono ya está registrado.'],
    ['E-mail (login) inválido.', 'Invalid login email.', 'Correo electrónico de acceso no válido.'],
    ['Informe um telefone válido com DDD (10 a 13 dígitos).', 'Enter a valid phone number with area code (10 to 13 digits).', 'Introduce un teléfono válido con prefijo (10 a 13 dígitos).'],
    ['A senha deve ter ao menos 6 caracteres.', 'The password must be at least 6 characters long.', 'La contraseña debe tener al menos 6 caracteres.'],
    ['A nova senha deve ter ao menos 6 caracteres (ou deixe em branco para manter a atual).', 'The new password must be at least 6 characters long (or leave it blank to keep the current one).', 'La nueva contraseña debe tener al menos 6 caracteres (o déjala en blanco para conservar la actual).'],
    ['Situação inválida.', 'Invalid status.', 'Estado no válido.'],
    ['Sessão expirada. Volte e tente novamente.', 'Session expired. Go back and try again.', 'La sesión ha caducado. Vuelve e inténtalo de nuevo.'],
    ['Informe o nome.', 'Enter a name.', 'Introduce un nombre.'],
    ['E-mail inválido.', 'Invalid email address.', 'Correo electrónico no válido.'],
    ['As senhas não conferem.', 'Passwords do not match.', 'Las contraseñas no coinciden.'],
    ['Cadastro realizado! A primeira mensalidade já está disponível; entre com seu e-mail e senha.', 'Registration complete! Your first subscription is available; sign in with your email and password.', '¡Registro completado! Tu primera cuota está disponible; inicia sesión con tu correo electrónico y contraseña.'],
    ['Não foi possível converter o texto do relatório para PDF.', 'Could not convert the report text to PDF.', 'No se pudo convertir el texto del informe a PDF.'],
    ['Nenhuma rota cadastrada neste período.', 'No routes registered for this period.', 'No hay rutas registradas en este período.'],
    ['Painel administrativo', 'Admin dashboard', 'Panel de administración'],
    ['Visão geral dos clientes da plataforma', 'Overview of platform clients', 'Resumen de los clientes de la plataforma'],
    ['Controle de acesso e cadastro de todos os clientes', 'Manage access and registrations for all clients', 'Administra el acceso y registro de todos los clientes'],
    ['Mensalidades, pagamentos e inadimplência dos clientes', 'Client subscriptions, payments, and overdue accounts', 'Cuotas, pagos y morosidad de los clientes'],
    ['Visão geral das rotas e custos', 'Overview of routes and expenses', 'Resumen de rutas y gastos'],
    ['Resumo das suas rotas e custos · Todo o histórico', 'Routes and expenses overview · All time', 'Resumen de rutas y gastos · Todo el historial'],
    ['Cadastre e acompanhe seus trajetos e valores', 'Register and track your routes and fares', 'Registra y consulta tus rutas e importes'],
    ['Cadastre, edite e acompanhe os trajetos e valores', 'Register, edit, and track routes and fares', 'Registra, edita y consulta tus rutas e importes'],
    ['Lance o que você gastou para realizar esta rota', 'Record expenses for this route', 'Registra los gastos de esta ruta'],
    ['Escolha uma rota para lançar os custos dela', 'Choose a route to record its expenses', 'Elige una ruta para registrar sus gastos'],
    ['A conta está inativa.', 'This account is inactive.', 'La cuenta está inactiva.'],
    ['Informe a cidade no formato CIDADE / UF, por exemplo JOINVILLE / SC.', 'Enter the city in CITY / STATE format, for example JOINVILLE / SC.', 'Introduce la ciudad con el formato CIUDAD / ESTADO, por ejemplo JOINVILLE / SC.'],
    ['Informe uma placa válida (modelo antigo ABC-1234 ou Mercosul ABC1D23).', 'Enter a valid license plate.', 'Introduce una matrícula válida.'],
    ['Informe um valor mensal válido.', 'Enter a valid monthly amount.', 'Introduce un importe mensual válido.'],
    ['O dia de vencimento deve ficar entre 1 e 31.', 'The due day must be between 1 and 31.', 'El día de vencimiento debe estar entre 1 y 31.'],
    ['Já existe outro cadastro com este e-mail.', 'Another account already uses this email.', 'Otra cuenta ya utiliza este correo electrónico.'],
    ['Este telefone já está cadastrado em outra conta.', 'This phone number is already registered to another account.', 'Este teléfono ya está registrado en otra cuenta.'],
    ['Cliente não encontrado.', 'Client not found.', 'No se encontró el cliente.'],
    ['Conta do cliente excluída.', 'Client account deleted.', 'Cuenta del cliente eliminada.'],
    ['Dados do cliente salvos.', 'Client details saved.', 'Datos del cliente guardados.'],
    ['Dados salvos e senha redefinida.', 'Details saved and password reset.', 'Datos guardados y contraseña restablecida.'],
    ['A senha deve ter ao menos 6 caracteres.', 'The password must be at least 6 characters long.', 'La contraseña debe tener al menos 6 caracteres.'],
    ['A nova senha deve ter ao menos 6 caracteres (ou deixe em branco para manter a atual).', 'The new password must be at least 6 characters long, or leave it blank to keep the current one.', 'La nueva contraseña debe tener al menos 6 caracteres o déjala en blanco para mantener la actual.'],
    ['Informe o nome.', 'Enter a name.', 'Introduce un nombre.'],
    ['E-mail inválido.', 'Invalid email address.', 'Correo electrónico no válido.'],
    ['Informe uma data válida.', 'Enter a valid date.', 'Introduce una fecha válida.'],
    ['Informe uma data válida.', 'Enter a valid date.', 'Introduce una fecha válida.'],
    ['Informe as datas inicial e final corretamente.', 'Enter valid start and end dates.', 'Introduce fechas de inicio y fin válidas.'],
    ['A data inicial não pode ser posterior à data final.', 'The start date cannot be after the end date.', 'La fecha de inicio no puede ser posterior a la fecha de fin.'],
    ['Selecione um período válido.', 'Select a valid period.', 'Selecciona un período válido.'],
    ['Escolha uma categoria válida.', 'Choose a valid category.', 'Elige una categoría válida.'],
    ['Informe um valor maior que zero.', 'Enter an amount greater than zero.', 'Introduce un importe mayor que cero.'],
    ['Rota atualizada.', 'Route updated.', 'Ruta actualizada.'],
    ['Rota cadastrada! Agora lance os custos dela.', 'Route registered! Now record its expenses.', '¡Ruta registrada! Ahora registra sus gastos.'],
    ['Rota excluída.', 'Route deleted.', 'Ruta eliminada.'],
    ['Rota não encontrada.', 'Route not found.', 'No se encontró la ruta.'],
    ['Custo lançado.', 'Expense recorded.', 'Gasto registrado.'],
    ['Custo atualizado.', 'Expense updated.', 'Gasto actualizado.'],
    ['Custo removido.', 'Expense removed.', 'Gasto eliminado.'],
    ['Custo não encontrado nesta rota.', 'Expense not found on this route.', 'No se encontró el gasto en esta ruta.'],
    ['Salvo.', 'Saved.', 'Guardado.'],
    ['Cadastro pessoal atualizado.', 'Personal details updated.', 'Datos personales actualizados.'],
    ['Escolha uma rota válida.', 'Choose a valid route.', 'Elige una ruta válida.'],
    ['Informe origem e destino.', 'Enter an origin and destination.', 'Introduce el origen y el destino.'],
    ['A distância (km) precisa ser maior que zero.', 'Distance (km) must be greater than zero.', 'La distancia (km) debe ser mayor que cero.'],
    ['Informe o valor por km.', 'Enter the rate per km.', 'Introduce la tarifa por km.'],
    ['Gerar mensalidades', 'Generate subscriptions', 'Generar cuotas'],
    ['Nova cobrança', 'New charge', 'Nuevo cobro'],
    ['Lançar cobrança', 'Create charge', 'Crear cobro'],
    ['Gerar mensalidades', 'Generate subscriptions', 'Generar cuotas'],
    ['Forma de pagamento', 'Payment method', 'Método de pago'],
    ['Marcar como paga', 'Mark as paid', 'Marcar como pagada'],
    ['Data do pagamento', 'Payment date', 'Fecha de pago'],
    ['Registrar pagamento', 'Record payment', 'Registrar pago'],
    ['A cobrança foi marcada como paga.', 'The charge was marked as paid.', 'El cobro se marcó como pagado.'],
    ['Cobrança removida.', 'Charge deleted.', 'Cobro eliminado.'],
    ['Pagamento estornado.', 'Payment reversed.', 'Pago revertido.'],
    ['Desfazer este pagamento? A cobrança volta a ficar em aberto.', 'Undo this payment? The charge will become outstanding again.', '¿Deshacer este pago? El cobro volverá a quedar pendiente.'],
    ['Excluir esta cobrança?', 'Delete this charge?', '¿Eliminar este cobro?'],
    ['Excluir esta rota e os custos dela?', 'Delete this route and its expenses?', '¿Eliminar esta ruta y sus gastos?'],
    ['Remover este custo?', 'Remove this expense?', '¿Eliminar este gasto?'],
    ['Conta inadimplente ou inativa fica offline: o cliente não consegue entrar (e quem estiver logado é desconectado) até você reativar.', 'Overdue or inactive accounts are disabled: the client cannot sign in until you reactivate the account.', 'Las cuentas morosas o inactivas se deshabilitan: el cliente no puede iniciar sesión hasta que reactives la cuenta.'],
    ['Remove o cliente e todas as rotas, custos e mensalidades dele. Não dá para desfazer.', 'This deletes the client and all of their routes, expenses, and subscriptions. This cannot be undone.', 'Esto elimina al cliente y todas sus rutas, gastos y cuotas. No se puede deshacer.'],
    ['Excluir DEFINITIVAMENTE a conta de', 'PERMANENTLY delete the account for', 'Eliminar PERMANENTEMENTE la cuenta de'],
    ['A conta atual não pode ser consultada.', 'The current account cannot be viewed.', 'No se puede consultar la cuenta actual.'],
    ['Nenhum cliente cadastrado.', 'No clients registered.', 'No hay clientes registrados.'],
    ['A vencer', 'Due soon', 'Por vencer'],
    ['Atrasada', 'Overdue', 'Vencida'],
    ['Pendente', 'Pending', 'Pendiente'],
    ['Em atraso', 'Overdue', 'Vencido'],
    ['Inadimplente', 'Overdue', 'Moroso'],
    ['Inativo', 'Inactive', 'Inactivo'],
    ['Ativo', 'Active', 'Activo'],
    ['Paga', 'Paid', 'Pagada'],
    ['Gerar mensalidades para este mês?', 'Generate subscriptions for this month?', '¿Generar cuotas de este mes?'],
    ['Nome, e-mail, cidade ou placa', 'Name, email, city, or license plate', 'Nombre, correo, ciudad o matrícula'],
    ['Motivo / observação', 'Reason / note', 'Motivo / observación'],
    ['Ex.: 2 mensalidades em aberto', 'E.g.: 2 outstanding subscriptions', 'Ej.: 2 cuotas pendientes'],
    ['Ex.: Posto Shell', 'E.g.: Shell gas station', 'Ej.: estación de servicio'],
    ['Ex.: 10,5', 'E.g.: 10.5', 'Ej.: 10,5'],
    ['Ex.: 5,89', 'E.g.: 5.89', 'Ej.: 5,89'],
    ['Não há clientes elegíveis para esta cobrança.', 'There are no eligible clients for this charge.', 'No hay clientes elegibles para este cobro.'],
    ['A mensalidade já existe para este cliente e mês.', 'A subscription already exists for this client and month.', 'Ya existe una cuota para este cliente y mes.'],
    ['Mensalidade lançada.', 'Subscription created.', 'Cuota creada.'],
    ['Mensalidades geradas.', 'Subscriptions generated.', 'Cuotas generadas.'],
    ['Não foi possível iniciar o arquivo de relatório.', 'Could not start the report file.', 'No se pudo iniciar el archivo del informe.'],
    ['DSK · Gestão de Custo', 'DSK · Cost Management', 'DSK · Gestión de Costos'],
    ['Acesso', 'Access', 'Acceso'],
    ['Concluir cadastro', 'Complete registration', 'Completar registro'],
    ['Situação da conta', 'Account status', 'Estado de la cuenta'],
    ['Relatório', 'Report', 'Informe'],
    ['Histórico', 'History', 'Historial'],
    ['Sem dados.', 'No data.', 'Sin datos.'],
    ['Calculando distância no OpenStreetMap...', 'Calculating distance with OpenStreetMap...', 'Calculando la distancia con OpenStreetMap...'],
    ['Não encontrei a origem no mapa. Tente incluir rua, número e bairro.', 'Could not find the origin on the map. Try adding the street, number, and neighborhood.', 'No se encontró el origen en el mapa. Prueba a añadir la calle, el número y el barrio.'],
    ['Não encontrei o destino no mapa. Tente incluir rua, número e bairro.', 'Could not find the destination on the map. Try adding the street, number, and neighborhood.', 'No se encontró el destino en el mapa. Prueba a añadir la calle, el número y el barrio.'],
    ['Não foi possível calcular agora. Verifique a conexão ou digite os km manualmente.', 'Could not calculate right now. Check your connection or enter the distance manually.', 'No se pudo calcular ahora. Comprueba la conexión o introduce la distancia manualmente.'],
    ['Origem', 'Origin', 'Origen'],
    ['Destino', 'Destination', 'Destino'],
    ['Valor calculado por trecho (R$)', 'Calculated amount per leg (BRL)', 'Importe calculado por tramo (BRL)'],
    ['Valor (R$)', 'Amount (BRL)', 'Importe (BRL)'],
    ['Cadastre o consumo (km/L) e o preço por litro em Dados pessoais.', 'Enter fuel consumption (km/L) and price per liter in Personal details.', 'Registra el consumo (km/L) y el precio por litro en Datos personales.'],
    ['Consumo estimado:', 'Estimated consumption:', 'Consumo estimado:'],
    ['· total da rota:', '· route total:', '· total de la ruta:'],
    ['Saldo pendente', 'Outstanding balance', 'Saldo pendiente'],
    ['Cliente atualizado.', 'Client updated.', 'Cliente actualizado.'],
    ['O e-mail já está sendo usado por outra conta.', 'This email is already used by another account.', 'Otra cuenta ya está usando este correo electrónico.'],
    ['O telefone já está sendo usado por outra conta.', 'This phone number is already used by another account.', 'Otra cuenta ya está usando este teléfono.'],
    ['Este cliente não pode ser excluído.', 'This client cannot be deleted.', 'No se puede eliminar este cliente.'],
    ['Não há mensalidades em aberto.', 'There are no outstanding subscriptions.', 'No hay cuotas pendientes.'],
    ['Nenhuma cobrança encontrada.', 'No charges found.', 'No se encontraron cobros.'],
    ['Nenhuma rota encontrada.', 'No routes found.', 'No se encontraron rutas.'],
    ['Nenhum custo encontrado.', 'No expenses found.', 'No se encontraron gastos.'],
    ['Tem certeza?', 'Are you sure?', '¿Estás seguro?'],
    ['Ocorreu um erro. Tente novamente.', 'An error occurred. Please try again.', 'Se produjo un error. Inténtalo de nuevo.'],
    ['Hoje', 'Today', 'Hoy'],
    ['Este mês', 'This month', 'Este mes'],
    ['Este ano', 'This year', 'Este año'],
    ['Receita das rotas', 'Route revenue', 'Ingresos de las rutas'],
    ['Custos das rotas', 'Route expenses', 'Gastos de las rutas'],
    ['Referência', 'Reference', 'Referencia'],
    ['Vencimento', 'Due date', 'Vencimiento'],
    ['Mensalidades da plataforma', 'Platform subscriptions', 'Cuotas de la plataforma'],
    ['Acompanhe suas cobranças e pagamentos. Para combinar o pagamento, fale com a administração; o sistema não processa pagamentos.', 'Track your charges and payments. Contact the administrator to arrange payment; the system does not process payments.', 'Consulta tus cobros y pagos. Contacta con la administración para acordar el pago; el sistema no procesa pagos.'],
    ['Nenhuma mensalidade registrada até o momento.', 'No subscriptions have been registered yet.', 'Todavía no hay cuotas registradas.'],
    ['Gerar relatório', 'Generate report', 'Generar informe'],
    ['Baixe um PDF organizado com o resumo das rotas, faturamento, gastos por categoria e lucro no período escolhido.', 'Download an organized PDF summarizing routes, revenue, expenses by category, and profit for the selected period.', 'Descarga un PDF organizado con el resumen de rutas, ingresos, gastos por categoría y beneficios del período seleccionado.'],
    ['Dia específico', 'Specific day', 'Día específico'],
    ['Mês', 'Month', 'Mes'],
    ['Ano', 'Year', 'Año'],
    ['Período personalizado', 'Custom period', 'Período personalizado'],
    ['Data inicial', 'Start date', 'Fecha inicial'],
    ['Data final', 'End date', 'Fecha final'],
    ['Baixar relatório PDF', 'Download PDF report', 'Descargar informe PDF'],
    ['Médias', 'Averages', 'Promedios'],
    ['Receita por km', 'Revenue per km', 'Ingresos por km'],
    ['Custo por km', 'Cost per km', 'Costo por km'],
    ['Lucro por rota', 'Profit per route', 'Beneficio por ruta'],
    ['Margem', 'Margin', 'Margen'],
    ['Custos por categoria', 'Expenses by category', 'Gastos por categoría'],
    ['Nenhum custo lançado no período.', 'No expenses recorded for this period.', 'No hay gastos registrados en este período.'],
    ['Últimas rotas do período', 'Recent routes in this period', 'Últimas rutas del período'],
    ['Exibindo até 8 rotas mais recentes. Os totais acima consideram todas as rotas do período selecionado.', 'Showing up to 8 most recent routes. Totals above include all routes in the selected period.', 'Se muestran hasta 8 rutas recientes. Los totales anteriores incluyen todas las rutas del período seleccionado.'],
    ['Rota', 'Route', 'Ruta'],
    ['Km', 'Km', 'Km'],
    ['Receita', 'Revenue', 'Ingresos'],
    ['Custo', 'Cost', 'Costo'],
    ['Nenhuma rota no período.', 'No routes in this period.', 'No hay rutas en este período.'],
    ['Controle de acesso e cadastro de todos os clientes', 'Manage access and registrations for all clients', 'Administra el acceso y registro de todos los clientes'],
    ['Rotas cadastradas na plataforma', 'Routes registered on the platform', 'Rutas registradas en la plataforma'],
    ['Últimos cadastros', 'Recent registrations', 'Registros recientes'],
    ['Cadastro', 'Registration', 'Registro'],
    ['Nenhum cliente encontrado.', 'No clients found.', 'No se encontraron clientes.'],
    ['Todos os clientes elegíveis', 'All eligible clients', 'Todos los clientes elegibles'],
    ['Gera a cobrança usando o valor e o dia de vencimento configurados para o cliente. Se já existir cobrança para o mês, ela não será duplicada. A geração automática diária também cria as cobranças no vencimento.', 'Creates charges using each client’s configured amount and due day. Existing charges for the month will not be duplicated. Daily automatic processing also creates charges on their due date.', 'Crea los cobros con el importe y el día de vencimiento configurados para cada cliente. No se duplicarán los cobros existentes del mes. El proceso automático diario también crea los cobros en su fecha de vencimiento.'],
    ['Selecione um cliente para gerar somente a mensalidade dele.', 'Select a client to generate only their subscription.', 'Selecciona un cliente para generar solo su cuota.'],
    ['Escolha um cliente.', 'Choose a client.', 'Elige un cliente.'],
    ['Informe um valor válido.', 'Enter a valid amount.', 'Introduce un importe válido.'],
    ['Informe uma referência válida.', 'Enter a valid reference.', 'Introduce una referencia válida.'],
    ['Já existe uma cobrança para este cliente neste mês.', 'A charge already exists for this client this month.', 'Ya existe un cobro para este cliente este mes.'],
    ['Cobrança lançada.', 'Charge created.', 'Cobro creado.'],
    ['A cobrança foi atualizada.', 'The charge was updated.', 'El cobro se actualizó.'],
    ['A cobrança foi removida.', 'The charge was deleted.', 'El cobro se eliminó.'],
    ['Cliente atualizado.', 'Client updated.', 'Cliente actualizado.'],
    ['Cliente cadastrado.', 'Client registered.', 'Cliente registrado.'],
    ['Informe uma senha.', 'Enter a password.', 'Introduce una contraseña.'],
    ['Cliente excluído.', 'Client deleted.', 'Cliente eliminado.'],
    ['Cadastro pessoal', 'Personal registration', 'Registro personal'],
    ['Preço do combustível (R$/L)', 'Fuel price (BRL/L)', 'Precio del combustible (BRL/L)'],
    ['de ida', 'one-way', 'de ida'],
    ['Valor total', 'Total amount', 'Importe total'],
    ['Não foi possível calcular agora. Verifique a conexão ou digite os km manualmente.', 'Could not calculate right now. Check your connection or enter the distance manually.', 'No se pudo calcular ahora. Comprueba la conexión o introduce la distancia manualmente.'],
    ['Contas offline por inadimplência', 'Accounts offline due to overdue payments', 'Cuentas sin acceso por morosidad'],
    ['Em aberto', 'Outstanding', 'Pendiente'],
    ['Dar baixa', 'Record payment', 'Registrar pago'],
    ['Últimos 6 meses', 'Last 6 months', 'Últimos 6 meses'],
    ['Sem mensalidades nos últimos meses.', 'No subscriptions in recent months.', 'No hay cuotas en los últimos meses.'],
    ['Confirmar pagamento', 'Confirm payment', 'Confirmar pago'],
    ['Forma', 'Method', 'Método'],
    ['Reativar a conta', 'Reactivate account', 'Reactivar la cuenta'],
    ['Só reativa se não restar nenhuma cobrança vencida.', 'The account is reactivated only when no overdue charges remain.', 'La cuenta solo se reactiva si no quedan cobros vencidos.'],
    ['Gere a mensalidade do mês e compartilhe os dados de pagamento com o cliente pelo canal habitual. Depois, registre aqui quando o pagamento for confirmado.', 'Create the monthly charge and share the payment details with the client through your usual channel. Then record the payment here once it is confirmed.', 'Genera el cobro mensual y comparte los datos de pago con el cliente por el canal habitual. Después, registra aquí el pago cuando se confirme.'],
    ['Gere as mensalidades para elas aparecerem nesta lista e no painel do cliente.', 'Generate subscriptions to show them here and on the client dashboard.', 'Genera las cuotas para que aparezcan aquí y en el panel del cliente.'],
    ['Todos os clientes elegíveis', 'All eligible clients', 'Todos los clientes elegibles'],
    ['Nenhuma conta offline.', 'No accounts are offline.', 'No hay cuentas sin acceso.'],
    ['Informações do cliente', 'Client information', 'Información del cliente'],
    ['Dados do cliente', 'Client details', 'Datos del cliente'],
    ['Cobrança mensal', 'Monthly billing', 'Cobro mensual'],
    ['Acesso do cliente (login e senha)', 'Client access (login and password)', 'Acceso del cliente (usuario y contraseña)'],
    ['Informações pessoais', 'Personal information', 'Información personal'],
    ['Nenhuma cobrança encontrada.', 'No charges found.', 'No se encontraron cobros.'],
    ['A cobrança foi registrada.', 'The charge was recorded.', 'El cobro se registró.'],
    ['O pagamento foi registrado.', 'The payment was recorded.', 'El pago se registró.'],
    ['Não há mensalidades nos últimos meses.', 'There are no subscriptions in recent months.', 'No hay cuotas en los últimos meses.'],
    ['Uma senha aleatória foi gerada.', 'A random password was generated.', 'Se generó una contraseña aleatoria.'],
    ['Placa inválida.', 'Invalid license plate.', 'Matrícula no válida.'],
    ['Cidade inválida. Use o formato CIDADE / UF.', 'Invalid city. Use the CITY / STATE format.', 'Ciudad no válida. Usa el formato CIUDAD / ESTADO.'],
    ['O e-mail informado já está em uso.', 'The email address is already in use.', 'El correo electrónico ya está en uso.'],
    ['O telefone informado já está em uso.', 'The phone number is already in use.', 'El teléfono ya está en uso.'],
    ['Escolha uma data válida.', 'Choose a valid date.', 'Elige una fecha válida.']
  ];

  var dictionaries = {
    en: new Map(translations.map(function (row) { return [row[0], row[1]]; })),
    es: new Map(translations.map(function (row) { return [row[0], row[2]]; }))
  };
  var originalText = new WeakMap();
  var originalAttributes = new WeakMap();
  var translating = false;

  function getLanguage() {
    var saved = localStorage.getItem(KEY);
    return saved === 'en' || saved === 'es' ? saved : 'pt';
  }

  function translateValue(value, language) {
    if (language === 'pt') return value;
    var dict = dictionaries[language];
    if (dict.has(value)) return dict.get(value);
    var match = value.match(/^Olá,\s*(.+)$/);
    if (match) return (language === 'en' ? 'Hello, ' : 'Hola, ') + match[1];
    match = value.match(/^(\d+)\s+rota\(s\) no período$/);
    if (match) return match[1] + (language === 'en' ? ' route(s) in period' : ' ruta(s) en el período');
    match = value.match(/^Novos clientes em (.+)$/);
    if (match) return (language === 'en' ? 'New clients in ' : 'Nuevos clientes en ') + match[1];
    match = value.match(/^(\d+)\s+cliente\(s\)$/);
    if (match) return match[1] + (language === 'en' ? ' client(s)' : ' cliente(s)');
    match = value.match(/^Cobranças \((\d+)\)$/);
    if (match) return (language === 'en' ? 'Charges (' : 'Cobros (') + match[1] + ')';
    match = value.match(/^(.+) · cliente desde (.+)$/);
    if (match) return match[1] + (language === 'en' ? ' · client since ' : ' · cliente desde ') + match[2];
    match = value.match(/^Resumo das suas rotas e custos · (.+)$/);
    if (match) {
      var period = translateValue(match[1], language);
      return (language === 'en' ? 'Routes and expenses overview · ' : 'Resumen de rutas y gastos · ') + period;
    }
    match = value.match(/^Distância de ida calculada:$/);
    if (match) return language === 'en' ? 'One-way distance calculated:' : 'Distancia de ida calculada:';
    match = value.match(/^Você ainda pode ajustar o valor manualmente\.$/);
    if (match) return language === 'en' ? 'You can still adjust the amount manually.' : 'Aún puedes ajustar el importe manualmente.';
    match = value.match(/^Consumo estimado: (.+) L · total da rota: R\$ (.+)$/);
    if (match) return (language === 'en' ? 'Estimated consumption: ' : 'Consumo estimado: ') + match[1] +
      ' L · ' + (language === 'en' ? 'route total: BRL ' : 'total de la ruta: R$ ') + match[2];
    return value;
  }

  function translateTextNode(node, language) {
    var current = node.nodeValue;
    if (!originalText.has(node)) originalText.set(node, current);
    var original = originalText.get(node);
    var leading = (original.match(/^\s*/) || [''])[0];
    var trailing = (original.match(/\s*$/) || [''])[0];
    var value = original.slice(leading.length, original.length - trailing.length);
    var translated = translateValue(value, language);
    var result = leading + translated + trailing;
    if (current !== result) node.nodeValue = result;
  }

  function translateElementAttributes(element, language) {
    var names = ['placeholder', 'title', 'aria-label', 'alt', 'data-confirm'];
    if (!originalAttributes.has(element)) originalAttributes.set(element, {});
    var originals = originalAttributes.get(element);
    names.forEach(function (name) {
      if (!element.hasAttribute(name)) return;
      if (!(name in originals)) originals[name] = element.getAttribute(name);
      var value = originals[name];
      var translated = translateValue(value, language);
      if (element.getAttribute(name) !== translated) element.setAttribute(name, translated);
    });
    if (element.matches('input[type="submit"], input[type="button"]')) {
      if (!('value' in originals)) originals.value = element.value;
      element.value = translateValue(originals.value, language);
    }
  }

  function translatePage(language) {
    if (translating) return;
    translating = true;
    document.documentElement.lang = language === 'en' ? 'en' : (language === 'es' ? 'es' : 'pt-BR');
    var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
      acceptNode: function (node) {
        return node.parentElement && !node.parentElement.closest('script,style,textarea,pre,code') &&
          node.nodeValue.trim() ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
      }
    });
    var node;
    while ((node = walker.nextNode())) translateTextNode(node, language);
    document.querySelectorAll('body *').forEach(function (element) {
      translateElementAttributes(element, language);
    });
    var title = document.querySelector('title');
    if (title) {
      if (!title.dataset.originalTitle) title.dataset.originalTitle = title.textContent;
      var originalTitle = title.dataset.originalTitle;
      title.textContent = translateValue(originalTitle, language);
      if (language !== 'pt' && originalTitle.indexOf('DSK - Gestão de Custo | ') === 0) {
        var pageTitle = originalTitle.slice('DSK - Gestão de Custo | '.length);
        title.textContent = (language === 'en' ? 'DSK - Cost Management | ' : 'DSK - Gestión de Costos | ') + translateValue(pageTitle, language);
      }
    }
    document.querySelectorAll('[data-language-select]').forEach(function (select) {
      select.value = language;
    });
    translating = false;
  }

  function applyLanguage(language) {
    if (language !== 'pt' && language !== 'en' && language !== 'es') language = 'pt';
    localStorage.setItem(KEY, language);
    translatePage(language);
  }

  document.addEventListener('change', function (event) {
    if (event.target.matches('[data-language-select]')) applyLanguage(event.target.value);
  });
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.action || form.action.indexOf('/cliente/relatorio.php') === -1) return;
    var input = form.querySelector('input[name="idioma"]');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'idioma';
      form.appendChild(input);
    }
    input.value = getLanguage();
  });
  document.addEventListener('click', function (event) {
    var link = event.target.closest('a[href]');
    if (!link || link.href.indexOf('/admin/relatorio-clientes.php') === -1) return;
    var url = new URL(link.href, window.location.href);
    url.searchParams.set('idioma', getLanguage());
    link.href = url.toString();
  });
  document.addEventListener('DOMContentLoaded', function () {
    applyLanguage(getLanguage());
    var observer = new MutationObserver(function (records) {
      if (translating) return;
      var language = getLanguage();
      translating = true;
      records.forEach(function (record) {
        record.addedNodes.forEach(function (added) {
          if (added.nodeType === Node.TEXT_NODE) {
            if (added.nodeValue.trim()) translateTextNode(added, language);
          } else if (added.nodeType === Node.ELEMENT_NODE) {
            translateElementAttributes(added, language);
            var walker = document.createTreeWalker(added, NodeFilter.SHOW_TEXT, null);
            var text;
            while ((text = walker.nextNode())) {
              if (text.nodeValue.trim()) translateTextNode(text, language);
            }
            added.querySelectorAll('*').forEach(function (element) {
              translateElementAttributes(element, language);
            });
          }
        });
      });
      translating = false;
    });
    observer.observe(document.body, { childList: true, subtree: true });
  });

  window.DSKLanguage = { applyLanguage: applyLanguage, getLanguage: getLanguage };
})();
