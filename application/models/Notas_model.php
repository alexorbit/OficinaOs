<?php
class Notas_model extends CI_Model
{

    /**
     * author: Ramon Silva
     * email: silva018-mg@yahoo.com.br
     *
     */

    public function __construct()
    {
        parent::__construct();
    }


    public function get($table, $fields, $where = '', $perpage = 0, $start = 0, $idEmpresa, $one = false, $array = 'array')
    {
        $this->db->select($fields);
        $this->db->from($table);
        //$this->db->where('idEmpresas', $idEmpresa);
        $this->db->order_by('dataFaturamento', 'desc');
        $this->db->limit($perpage, $start);
        if ($where) {
            $this->db->where($where);
        }

        $query = $this->db->get();

        $result =  !$one  ? $query->result() : $query->row();
        return $result;
    }

    public function getById($id)
    {
        $this->db->where('idNotas', $id);
        $this->db->limit(1);
        return $this->db->get('notas')->row();
    }

    public function getByChave($chave)
    {
        $this->db->where('chave', $chave);
        $this->db->limit(1);
        return $this->db->get('notas')->row();
    }

    public function getByNota($notas_id)
    {
        $this->db->where('notas_id', $notas_id);
        $this->db->limit(1);
        return $this->db->get('notas_produtos')->row();
    }

    public function getClienteByCnpj($cnpj)
    {
        $this->db->where('documento', $cnpj);
        $this->db->limit(1);
        return $this->db->get('clientes')->row();
    }

    public function add($table, $data)
    {
        $this->db->insert($table, $data);
        if ($this->db->affected_rows() == '1') {
            return true;
        }

        return false;
    }

    public function upsert($table, $data, $fieldID = null, $ID = null)
    {
        $this->db->select('*');
        $this->db->where($fieldID, $ID);
        $q = $this->db->get($table);
        // validando
        //$q = $query->result();

        if ($q !== FALSE && $q->num_rows() > 0) {
            $this->db->where($fieldID, $ID);
            $this->db->update($table, $data);
            if ($this->db->affected_rows() == '1') {
                return true;
            }
        } else {
            // VALIDAR
            //$ID = strtoupper(dechex(microtime(true) * 1000) . bin2hex(random_bytes(8)));
            //$this->db->set($fieldID, $ID);
            $this->db->insert($table, $data);
            if ($this->db->affected_rows() == '1') {
                return true;
            }
        }

        // $this->db->insert($table, $data);
        // if ($this->db->affected_rows() == '1') {
        //     return true;
        // }

        return false;
    }

    public function edit($table, $data, $fieldID, $ID)
    {
        $this->db->where($fieldID, $ID);
        $this->db->update($table, $data);

        if ($this->db->affected_rows() >= 0) {
            return true;
        }

        return false;
    }

    public function delete($table, $fieldID, $ID)
    {
        $this->db->where($fieldID, $ID);
        $this->db->delete($table);
        if ($this->db->affected_rows() == '1') {
            return true;
        }

        return false;
    }

    public function count($table)
    {
        return $this->db->count_all($table);
    }

    public function updateEstoque($produto, $quantidade, $operacao = '-')
    {
        $sql = "UPDATE produtos set estoque = estoque $operacao ? WHERE idProdutos = ?";
        return $this->db->query($sql, [$quantidade, $produto]);
    }

    function _localizarProdutos($descProduto, $codProduto)
    {
        $this->db->select('*');
        $this->db->from('produtos');
        $this->db->where('descricao', $descProduto);
        $this->db->or_where('codDeBarra', $codProduto);
        $query = $this->db->get();
        $result = $query->result();
        if ($result == null) {
            return null;
        }
        return $result;
    }
}
