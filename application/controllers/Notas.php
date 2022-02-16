<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Notas extends MY_Controller
{

    /**
     * author: Ramon Silva
     * email: silva018-mg@yahoo.com.br
     *
     */

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('form');
        $this->load->model('notas_model');
        $this->load->model('produtos_model');
        $this->load->model('clientes_model');
        $this->data['menuNotas'] = 'Notas';
    }

    public function index()
    {
        $this->gerenciar();
    }

    public function gerenciar()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar notas.');
            redirect(base_url());
        }
        $idEmpresa = $this->session->userdata('idEmpresas');

        $this->load->library('pagination');

        $this->data['configuration']['base_url'] = site_url('notas/gerenciar/');
        $this->data['configuration']['total_rows'] = $this->notas_model->count('notas');

        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->notas_model->get('notas', '*', '', $this->data['configuration']['per_page'], $this->uri->segment(3), $idEmpresa);

        $this->data['view'] = 'notas/notas';
        return $this->layout();
    }

    public function adicionar()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'aProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para adicionar notas.');
            redirect(base_url());
        }
        $idEmpresa = $this->session->userdata('idEmpresas');

        $directory = 'assets/uploads/' . $idEmpresa . '/XMLs' . '/';

        if (!is_dir($directory)) {
            exit('Diretorio inválido');
        }
        $files = array();
        foreach (scandir($directory) as $file) {
            if (
                $file !== '.'
                && $file !== '..'
                && $file !== 'mpdf'
                && (pathinfo($file, PATHINFO_EXTENSION) === 'xml'
                    || pathinfo($file, PATHINFO_EXTENSION) === 'XML')
            ) {
                echo '<br/>';
                $files[] = $file;
            }
        }

        $inserted = false;
        $posts = $this->input->post('sequencialProduto');
        $codProdutos = $this->input->post('codigoProduto');
        foreach ($posts as $key => $post_id) {
            if (empty($codProdutos[$key])) continue;
            (int)$estoqueAtualBanco = 0;
            $produtos = $this->produtos_model->getByCodDeBarra($this->input->post('codigoProduto')[$key]);
            if ($produtos != null || $produtos != "") {
                $estoqueAtualBanco = $produtos->estoque;
            }
            $estoqueXML = (int)$this->input->post('quantidadeItem')[$key];
            $estoque = $estoqueXML + $estoqueAtualBanco;

            $dataFaturamento = $this->input->post('dataSaida');
            $dataFaturamento = explode(' ', $dataFaturamento);
            $diaFaturamento = explode('/', $dataFaturamento[0]);
            $horaFaturamento = $dataFaturamento[1]; //explode(':', $dataFaturamento[1]);
            $dia = $diaFaturamento[0];
            $mes = $diaFaturamento[1];
            $ano = $diaFaturamento[2];
            $dataFaturamento = $ano . '-' . $mes . '-' . $dia . ' ' . $horaFaturamento;
            $nota = $this->input->post('nNota');
            $serie = $this->input->post('nSerie');
            $chave = $this->input->post('chaveNf');
            $tpOperacao = $this->input->post('tpOperacao');
            $valorDescontos = str_replace(",", ".", $this->input->post('valorDescontos'));
            $valorFrete = str_replace(",", ".", $this->input->post('valorFrete'));

            $codDeBarra = $this->input->post('codigoProduto')[$key];
            $descricao = $this->input->post('descricaoProduto')[$key];
            $precoCompra = str_replace(",", ".", $this->input->post('valorUnitario')[$key]);

            $codDeBarra = $codDeBarra;
            $descricao = $this->input->post('descricaoProduto')[$key];
            $unidade = 'UNID';
            $precoCompra = str_replace(",", ".", $this->input->post('valorUnitario')[$key]);
            $precoVenda = str_replace(",", ".", $this->input->post('valorUnitario')[$key]);
            $estoque = $estoque;
            $estoqueMinimo = '0';
            $saida = '1';
            $entrada = '1';

            $queryRollBack = "-- ROLLBACK
                            ROLLBACK";
            $queryProdutos = "
            BEGIN TRAN
            -- INSERINDO PRODUTOS
            IF EXISTS	(SELECT 1 FROM produtos WHERE codDeBarra = '$codDeBarra' AND idEmpresas = '$idEmpresa')
            BEGIN
                UPDATE produtos SET codDeBarra = '$codDeBarra', idEmpresas = '$idEmpresa', 
                    descricao = '$descricao', unidade = '$unidade', 
                    precoCompra = '$precoCompra', 
                    --precoVenda = '$precoVenda', 
                    estoque = $estoque, 
                    estoqueMinimo = '$estoqueMinimo', saida = '$saida', entrada = '$entrada'
                WHERE codDeBarra = '$codDeBarra'
                AND idEmpresas = '$idEmpresa'
            END
            ELSE
            BEGIN
                INSERT INTO produtos (codDeBarra, idEmpresas, descricao, unidade, precoCompra, precoVenda, estoque, estoqueMinimo, saida, entrada)
                VALUES ('$codDeBarra', '$idEmpresa', '$descricao', '$unidade', '$precoCompra', '$precoVenda', $estoque, '$estoqueMinimo', '$saida', '$entrada')
            END
            ";

            $ch = curl_init(); //Inicializa
            curl_setopt($ch, CURLOPT_URL, "https://www.receitaws.com.br/v1/cnpj/" . $this->input->post('cnpjCpf')); //Acessa a URL
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Permite a captura do Retorno
            $retorno = curl_exec($ch); //Executa o cURL e guarda o Retorno em uma variável
            curl_close($ch); //Encerra a conexão
            $retorno = json_decode($retorno);
            $telefonebase = explode('/', $retorno->telefone);
            $telefone = null;
            $celular = null;
            $email = $retorno->email;
            $nomeClienteFornecedor = $retorno->nome;
            $rua = $retorno->logradouro;
            $bairro = $retorno->bairro;
            $numero = $retorno->numero;
            $cidade = $retorno->municipio;
            $estado = $retorno->uf;
            $cep = $retorno->cep;
            $complemento = $retorno->complemto;
            $cnpj = $this->input->post('cnpjCpf');
            $telefone = null ? $telefonebase : $telefonebase[0];
            $celular = null ? $telefonebase : $telefonebase[1];
            $query = "
            -- INSERINDO NOTAS / PRODUTOS NOTAS
            DECLARE @IdNotas varchar(128) = NEWID();
            DECLARE @IdProdutos_Notas varchar(128) = NEWID();
            DECLARE @IdProdutos varchar(128) = NEWID();
            DECLARE @IdClientes varchar(128) = NEWID();
            DECLARE @ValorTotalNotas decimal(9,2) = 0;
            IF NOT EXISTS	(SELECT 1 FROM NOTAS WHERE CHAVE = '$chave' AND idEmpresas = '$idEmpresa')
	            BEGIN
                    INSERT INTO notas (idNotas, idEmpresas, dataFaturamento, nota, serie, chave, tipoOperacao, valorTotal, descontos, frete, peso, XML, volume, url)
                    OUTPUT inserted.idNotas
                    VALUES (@IdNotas, '$idEmpresa', '$dataFaturamento', '$nota', '$serie', '$chave', '$tpOperacao', 0, $valorDescontos, $valorFrete, null, null, null, null)
                END
                ELSE
				BEGIN
					SELECT @IdNotas = idNotas FROM NOTAS WHERE CHAVE = '$chave' AND idEmpresas = '$idEmpresa'
                    SELECT @IdNotas
				END
                    IF OBJECT_ID(N'tempdb..##tempIdNotas') IS NOT NULL
						BEGIN
						DROP TABLE ##tempIdNotas
						END
					CREATE TABLE ##tempIdNotas  
					(  
					    IdNotas   varchar(128),  
					    Chave varchar(max)
					); 
					INSERT INTO ##tempIdNotas
					VALUES (@IdNotas, '$chave')

                    SELECT @IdProdutos = isnull(A.idProdutos,@IdProdutos) FROM produtos A WHERE codDeBarra = '$codDeBarra' AND idEmpresas = '$idEmpresa'

                    IF EXISTS	(SELECT 1 FROM produtos WHERE codDeBarra = '$codDeBarra' AND idEmpresas = '$idEmpresa')
                    BEGIN
                        SELECT @IdProdutos = idProdutos FROM produtos WHERE codDeBarra = '$codDeBarra' AND idEmpresas = '$idEmpresa'
                    END
                    ELSE
                    BEGIN
                        SELECT @IdProdutos = @IdProdutos
                    END

                    IF NOT EXISTS   ((SELECT 1 FROM produtos_notas WHERE notas_id = @IdNotas AND produtos_id = @IdProdutos))
                    BEGIN
		                INSERT INTO produtos_notas (idProdutos_notas, idEmpresas, quantidade, descricao, preco, notas_id, produtos_id)
		                VALUES (@IdProdutos_Notas, '$idEmpresa', $estoqueXML, '$descricao', '$precoCompra', @IdNotas, @IdProdutos)
                    END
                    SELECT @ValorTotalNotas = SUM(PRECO) FROM PRODUTOS_NOTAS WHERE NOTAS_ID = @IDNOTAS AND idEmpresas = '$idEmpresa'
                    UPDATE NOTAS SET VALORTOTAL = @ValorTotalNotas
					WHERE IDNOTAS = @IDNOTAS
                    AND idEmpresas = '$idEmpresa'
					AND valorTotal <> @ValorTotalNotas

                    IF NOT EXISTS (SELECT 1 FROM clientes WHERE replace(replace(replace(documento,'/',''),'.',''),'-','') = replace(replace(replace('$cnpj','/',''),'.',''),'-',''))
					BEGIN
						INSERT INTO clientes (nomeCliente, sexo, pessoa_fisica, documento, telefone, celular, email, dataCadastro, rua, numero, bairro, cidade, estado, cep, contato, complemento, fornecedor)
						VALUES ('$nomeClienteFornecedor', null, 1, '$cnpj', '$telefone', '$celular', '$email', GETDATE(), '$rua', '$numero', '$bairro', '$cidade', '$estado', '$cep', null, '$complemento', 1)
					END
					ELSE
					BEGIN
						SELECT @IdClientes = idClientes FROM clientes WHERE replace(replace(replace('documento','/',''),'.',''),'-','') = replace(replace(replace('$cnpj','/',''),'.',''),'-','')
					END
                COMMIT
                ";
            if ($this->db->query($queryProdutos)) {
                if ($this->db->query($query)) {
                    $inserted = false;
                    if ($this->input->post('sequencialFatura') !=  null) {
                        $seqFaturas = $this->input->post('sequencialFatura');
                        $faturaSeq = $this->input->post('parcelaFatura');
                        $nFatura = $this->input->post('nFaturaDuplicata');
                        $usuarios_id = $this->session->userdata('id');

                        // $clientes = $this->notas_model->getClienteByCnpj($cnpj);
                        // $idClientes = $clientes->idClientes;
                        // $nomeCliente = $clientes->nomeCliente;

                        foreach ($seqFaturas as $keyFatura => $post_id) {
                            if (empty($faturaSeq[$keyFatura])) continue;

                            $parcelaFatura = $this->input->post('parcelaFatura')[$keyFatura];
                            $vencimentoFatura = explode('/', $this->input->post('vencimentoFatura')[$keyFatura]);
                            $vencimentoFatura = $vencimentoFatura[2] . '-' . $vencimentoFatura[1] . '-' . $vencimentoFatura[0];

                            if ($this->input->post('bandeiraCartao') != null) {
                                $bandeira = 'Bandeira Cartao: ' . $this->input->post('bandeiraCartao');
                            }
                            if ($this->input->post('autorizacao') != null) {
                                $autorizacao = 'Autorização: ' . $this->input->post('autorizacao');
                            }
                            //$celular = null ? $telefonebase : $telefonebase[1];
                            $bandeira != null ? $bandeira : null;
                            $autorizacao != null ? $autorizacao : null;
                            $obs = $bandeira . $autorizacao;
                            $descricao  = "Fatura/Duplicata: ($nFatura) Parcela: ($parcelaFatura)";
                            $valor = str_replace(",", ".", $this->input->post('valorFatura')[$keyFatura]);
                            $forma_pgto = $this->input->post('tipoPgto');
                            $observacoes = $obs == "" ? null : $obs;
                            $dadosLancamentos = [
                                'descricao' => $descricao,
                                'valor' => str_replace(",", ".", $this->input->post('valorFatura')[$keyFatura]),
                                'data_vencimento' => $vencimentoFatura,
                                'data_pagamento' => null,
                                'baixado' => '0',
                                'cliente_fornecedor' => '$nomeCliente',
                                'forma_pgto' => $this->input->post('tipoPgto'),
                                'tipo' => 'despesa',
                                'anexo' => null, // verificar se podemos colocar o xml
                                'observacoes' => $obs == "" ? null : $obs,
                                'clientes_id' => '$idClientes',
                                'usuarios_id' => $usuarios_id,
                                'notas_id' => $usuarios_id,
                                'idEmpresas' => $idEmpresa,
                            ];
                            $queryLancamentos = "                            
                            -- INSERINDO LANÇAMENTOS
                            BEGIN TRAN
                            DECLARE @IdLancamentos varchar(128) = NEWID();
                            DECLARE @IdClientes varchar(128) = NEWID();
                            DECLARE @NomeCliente varchar(max) = NEWID();

                            SELECT
                                @NomeCliente = nomeCliente,
                                @IdClientes = idClientes
                                FROM clientes WHERE documento = '$cnpj'

                            SELECT * FROM ##tempIdNotas

                            IF EXISTS	(SELECT 1 FROM lancamentos WHERE idEmpresas = '$idEmpresa' AND descricao = '$descricao' AND clientes_id = @IdClientes)
                            BEGIN
                                select '' as abc
                                --UPDATE lancamentos SET codDeBarra = '$codDeBarra', idEmpresas = '$idEmpresa', 
                                --    descricao = '$descricao', unidade = '$unidade', 
                                --    precoCompra = '$precoCompra', 
                                --    --precoVenda = '$precoVenda', 
                                --    estoque = $estoque, 
                                --    estoqueMinimo = '$estoqueMinimo', saida = '$saida', entrada = '$entrada'
                                --WHERE codDeBarra = '$codDeBarra'
                                --AND idEmpresas = '$idEmpresa'
                            END
                            ELSE
                            BEGIN
                                INSERT INTO lancamentos (idLancamentos, descricao, valor, data_vencimento, data_pagamento, baixado, cliente_fornecedor, forma_pgto, tipo, observacoes, clientes_id, notas_id, usuarios_id, idEmpresas)
                                VALUES (@IdLancamentos, '$descricao', '$valor', '$vencimentoFatura', null, '0', @NomeCliente, '$forma_pgto', 'despesa', '$observacoes', @IdClientes, @IdNotas, '$usuarios_id', '$idEmpresa')
                            END
                            COMMIT
                            ";
                            if (
                                $this->db->query($queryLancamentos)
                                //$this->notas_model->upsert('lancamentos', $dadosLancamentos, 'descricao', $descricao) == true
                            ) {
                            }
                        }
                    }
                } else {
                    $this->db->query($queryRollBack);
                    $this->session->set_flashdata('error', 'Erro ao inserir Nota e Produtos do XML');
                    redirect(site_url('notas'));
                }
                $inserted = true;
            } else {
                $this->db->query($queryRollBack);
                $this->session->set_flashdata('error', 'Erro ao inserir Produtos do XML');
                redirect(site_url('notas'));
            }
        }

        if ($inserted) {
            $this->session->set_flashdata('success', 'XML Importado com sucesso!');
            log_info('Adicionou um xml');
            redirect(site_url('notas'));
        } else {
            $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro.</p></div>';
        }
    }

    public function editar()
    {
        /* function check_file_exists_here($url)
        {
            $result = get_headers($url);
            return stripos($result[0], "200 OK") ? true : false; //check if $result[0] has 200 OK
        } */
        $idEmpresa = $this->session->userdata('idEmpresas');

        if (strtolower($this->uri->segment(3)) != strtolower(str_replace('assets/uploads/' . $idEmpresa . '/' . 'XMLs/', '', $this->session->userdata('xml')))) {
            $this->session->set_flashdata('error', 'Favor refazer o Upload do XML');
            redirect(site_url('notas'));
        }
        if (!$this->uri->segment(3) || !is_numeric($this->uri->segment(3))) {
            //if (!$this->uri->segment(3)) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('notas');
        }

        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'eProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para editar produtos.');
            redirect(base_url());
        }
        $xml = strtolower($this->session->userdata('xml'));
        $diretorioParaUpload = 'assets/uploads/' . $idEmpresa . '/XMLs' . '/';
        $arquivo = base_url() . $diretorioParaUpload . $xml . '.xml';

        //if (check_file_exists_here($arquivo)) {
            $nota = simplexml_load_file($arquivo);
            // imprime os atributos do objeto criado
            //echo $nota;
            if (empty($nota->protNFe->infProt->nProt)) {
                $this->session->set_flashdata('error', 'Arquivo sem dados de autorização');
                redirect(site_url('notas'));
            }

            // $xml = new DOMDocument();
            // $xml->load($arquivo);
            $this->data['xml'] = $nota;

            $this->data['view'] = 'notas/editarNota';
            return $this->layout();
        //}
    }

    public function visualizar()
    {
        // if (!$this->uri->segment(3) || !is_numeric($this->uri->segment(3))) {
        //     $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
        //     redirect('notas');
        // }

        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'vProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar nota.');
            redirect(base_url());
        }

        $this->data['result'] = $this->notas_model->getByChave($this->uri->segment(3));

        if ($this->data['result'] == null) {
            $this->session->set_flashdata('error', 'Notas não encontrado.');
            redirect(site_url('notas/editar/') . $this->input->post('idNotas'));
        }

        $this->data['view'] = 'notas/visualizarNota';
        return $this->layout();
    }

    public function excluir()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'dProduto')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para excluir produtos.');
            redirect(base_url());
        }

        $id = $this->input->post('idNotas');
        if ($id == null) {
            $this->session->set_flashdata('error', 'Erro ao tentar excluir nota.' . var_dump($this->input->post('id')));
            redirect(base_url() . 'index.php/notas/gerenciar/');
        }

        $this->notas_model->delete('produtos_notas', 'notas_id', $id);
        $this->notas_model->delete('notas', 'idNotas', $id);

        log_info('Removeu uma notas. ID: ' . $id);

        $this->session->set_flashdata('success', 'notas excluida com sucesso!');
        redirect(site_url('notas/gerenciar/'));
    }

    public function do_upload()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'aArquivo')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para adicionar arquivos.');
            redirect(base_url());
        }
        $idEmpresa = $this->session->userdata('idEmpresas');

        $date = date('d-m-Y');

        $config['upload_path'] = './assets/uploads/' . $idEmpresa . '/XMLs' . '/';
        $config['allowed_types'] = 'xml|XML';
        $config['max_size'] = 0;
        // $config['max_width'] = '3000';
        // $config['max_height'] = '2000';
        $config['encrypt_name'] = false;

        if (!is_dir('./assets/uploads/' . $idEmpresa . '/XMLs')) {
            mkdir('./assets/uploads/' . $idEmpresa . '/XMLs', 0777, true);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload()) {
            $error = ['error' => $this->upload->display_errors()];

            $this->session->set_flashdata('error', 'Erro ao fazer upload do arquivo, verifique se a extensão do arquivo é permitida.');
            redirect(site_url('notas'));
        } else {
            //$data = array('upload_data' => $this->upload->data());
            return $this->upload->data();
        }
    }
    // upload unitario
    public function uploadXML()
    {
        if (isset($_POST['uploadBtn']) && $_POST['uploadBtn'] == 'Upload') {
            if (isset($_FILES['uploadedFile']) && $_FILES['uploadedFile']['error'] === UPLOAD_ERR_OK) {
                $idEmpresa = $this->session->userdata('idEmpresas');
                $arquivoTmpPath = $_FILES['uploadedFile']['tmp_name'];
                $arquivoNome = $_FILES['uploadedFile']['name'];
                $arquivoTamanho = $_FILES['uploadedFile']['size'];
                $arquivoTipo = $_FILES['uploadedFile']['type'];
                $arquivoNomeSemExtensao = explode(".", $arquivoNome);
                $arquivoExtensao = strtolower(end($arquivoNomeSemExtensao));

                $arquivo = $arquivoNomeSemExtensao[0] . '.' . $arquivoExtensao;
                $extensoesPermitidas = array('xml', 'XML');

                if (in_array($arquivoExtensao, $extensoesPermitidas)) {
                    $diretorioParaUpload = 'assets/uploads/' . $idEmpresa . '/XMLs' . '/';
                    $diretorioComArquivo = $diretorioParaUpload . $arquivo; // . '.' . $arquivoExtensao;

                    if (!is_dir('./assets/uploads/' . $idEmpresa . '/XMLs')) {
                        mkdir('./assets/uploads/' . $idEmpresa . '/XMLs', 0777, true);
                    }

                    //TODO:  descomentar isso depois
                    // if (file_exists($diretorioComArquivo)) {
                    //     $this->session->set_flashdata('error', 'Arquivo XML já importado');
                    //     redirect(site_url('notas'));
                    // }
                    if (move_uploaded_file($arquivoTmpPath, $diretorioComArquivo)) {
                        $this->session->set_flashdata('success', 'XML importado com sucesso!');
                        $_SESSION['xml'] = $arquivoNomeSemExtensao[0];
                        redirect(site_url('notas/editar/' . $arquivoNomeSemExtensao[0]));
                    } else {
                        $this->session->set_flashdata('error', 'Ocorreu um erro ao mover o arquivo para o diretório de upload (' . $diretorioParaUpload . '). Certifique-se de que o diretório de upload seja gravável pelo servidor da web.');
                    }
                } else {
                    $this->session->set_flashdata('error', 'Falha no Upload, arquivos suportados: ' . implode(',', $extensoesPermitidas));
                }
            } else {
                $this->session->set_flashdata('error', 'Ocorreu um erro no upload do arquivo.');
            }
        }
        redirect(site_url('notas'));
        //header("Location: ../notas");
    }

    public function localizarProdutos()
    {
        $descProduto = $_POST['descProduto'];
        $codProduto = $_POST['codProduto'];
        $retorno =  $this->notas_model->_localizarProdutos($descProduto, $codProduto);

        if ($retorno) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode($retorno));
        } else {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode(['message' => "Produto / Código Não localizado"]));
        }
    }
}
