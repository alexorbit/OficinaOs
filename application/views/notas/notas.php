<?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'aOs')) { ?>
 <?php } ?>
<div class="widget-box">
    <div class="widget-title">
        <span class="icon">
            <i class="fas fa-file-invoice-dollar"></i>
        </span>
        <h5>Notas</h5>
    </div>
    <div class="widget-content nopadding tab-content">
        <table id="tabela" class="table table-bordered ">
            <thead>
                <tr style="backgroud-color: #2D335B">
                    <th>Cod. Nota</th>
                    <th>Nota</th>
                    <th>Série</th>
                    <th>Chave</th>
                    <th>Valor</th>
                    <th>Frete</th>
                    <th>Desconto</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php

                if (!$results) {
                    echo '<tr>
                                <td colspan="5">Nenhuma Nota Importada</td>
                                </tr>';
                }
                foreach ($results as $r) {
                    echo '<tr>';
                    echo '<td>' . $r->idNotas . '</td>';
                    echo '<td>' . $r->nota . '</td>';
                    echo '<td>' . $r->serie . '</td>';
                    echo '<td>' . $r->chave . '</td>';
                    echo '<td>' . number_format($r->valorTotal, 2, ',', '.') . '</td>';
                    echo '<td>' . number_format($r->frete, 2, ',', '.') . '</td>';
                    echo '<td>' . number_format($r->desconto, 2, ',', '.') . '</td>';
                    echo '<td>';
                    if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vProduto')) {
                        echo '<a style="margin-right: 1%" href="' . base_url() . 'index.php/notas/visualizar/' . $r->idNotas . '" class="btn tip-top" title="Visualizar Nota"><i class="fas fa-eye"></i></a>  ';
                    }
                    // if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eProduto')) {
                    //     echo '<a style="margin-right: 1%" href="' . base_url() . 'index.php/notas/editar/' . $r->chave . '" class="btn btn-info tip-top" title="Editar Nota"><i class="fas fa-edit"></i></a>';
                    // }
                    if ($this->permission->checkPermission($this->session->userdata('permissao'), 'dProduto')) {
                        echo '<a style="margin-right: 1%" href="#modal-excluir" role="button" data-toggle="modal" nota="' . $r->idNotas . '" class="btn btn-danger tip-top" title="Excluir Nota"><i class="fas fa-trash-alt"></i></a>';
                    }
                    // if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eProduto')) {
                    //     echo '<a href="#atualizar-estoque" role="button" data-toggle="modal" produto="' . $r->chave . '" estoque="' . $r->estoque . '" class="btn btn-primary tip-top" title="Atualizar Estoque"><i class="fas fa-plus-square"></i></a>';
                    // }
                    echo '</td>';
                    echo '</tr>';
                } ?>
            </tbody>
        </table>
    </div>
</div>
<?php echo $this->pagination->create_links(); ?>

<!-- Modal -->
<div id="modal-excluir" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <form action="<?php echo base_url() ?>index.php/notas/excluir" method="post">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h5 id="myModalLabel"><i class="fas fa-trash-alt"></i> Excluir Nota</h5>
        </div>
        <div class="modal-body">
            <input type="hidden" id="idNota" class="idNota" name="id" value="" />
            <h5 style="text-align: center">Deseja realmente excluir está nota?</h5>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
            <button class="btn btn-danger">Excluir</button>
        </div>
    </form>
</div>

<!-- Modal Importar XML -->
<div id="modal-importar-xml" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <form method="POST" action="notas/uploadXML" enctype="multipart/form-data">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        </div>
        <div class="modal-body">
            <div class="control-group">
                <div class="upload-wrapper">
                    <span class="file-name">Escolher XML...</span>
                    <label for="file-upload"><input type="file" id="file-upload" name="uploadedFile"></label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
            <button class="btn btn-primary" type="submit" name="uploadBtn" value="Upload">Importar</button>
        </div>

    </form>
</div>

<!-- Modal Etiquetas -->
<div id="modal-importar-tudo" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <form action="<?php echo base_url() ?>index.php/relatorios/produtosEtiquetas" method="get">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h5 id="myModalLabel">Gerar etiquetas com Código de Barras</h5>
        </div>
        <div class="modal-body">
            <div class="span12 alert alert-info" style="margin-left: 0"> Escolha o intervalo de produtos para gerar as etiquetas.</div>

            <div class="span12" style="margin-left: 0;">
                <div class="span6" style="margin-left: 0;">
                    <label for="valor">De</label>
                    <input class="span9" style="margin-left: 0" type="text" id="de_id" name="de_id" placeholder="ID do primeiro produto" value="" />
                </div>


                <div class="span6">
                    <label for="valor">Até</label>
                    <input class="span9" type="text" id="ate_id" name="ate_id" placeholder="ID do último produto" value="" />
                </div>

                <div class="span4">
                    <label for="valor">Qtd. do Estoque</label>
                    <input class="span12" type="checkbox" name="qtdEtiqueta" value="true" />
                </div>

                <div class="span6">
                    <label class="span12" for="valor">Formato Etiqueta</label>
                    <select name="etiquetaCode">
                        <option value="EAN13">EAN-13</option>
                        <option value="UPCA">UPCA</option>
                        <option value="C93">CODE 93</option>
                        <option value="C128A">CODE 128</option>
                        <option value="CODABAR">CODABAR</option>
                        <option value="QR">QR-CODE</option>
                    </select>
                </div>

            </div>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
            <button class="btn btn-success">Gerar</button>
        </div>
    </form>
</div>