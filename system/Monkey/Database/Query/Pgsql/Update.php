<?php

namespace Monkey\Database\Query\Pgsql;

use Monkey\Database\Query;

class Update extends Query\Update
{

    /**
     * @param Connection $connection
     * @param $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->connection=$connection;
        $this->queryIdentifier=uniqid('', TRUE);
        $this->table = $table;
        $this->condition = new Condition($this->queryIdentifier,'AND');
    }

    protected function compile()
    {
        $fields = $this->fields;
        $update_fields = array();
        $update_values = array();
        foreach ($this->expressionFields as $field => $data) {
            !empty($data['arguments']) and $update_values += $data['arguments'];
            if ($data['expression'] instanceof Select) {
                $update_values += $data['expression']->getArguments($this->queryIdentifier);
                $data['expression'] = ' (' . $data['expression']->getString($this->queryIdentifier) . ')';
            }
            $update_fields[] = $field . '=' . $data['expression'];
            unset($fields[$field]);
        }
        $max_placeholder = $placeholder=0;
        foreach ($fields as $field => $value) {
            $placeholder=':mk_update_placeholder_' . ($max_placeholder++);
            $update_fields[] = $field . '=' . $placeholder;
            $update_values[$placeholder] = $value;
        }
        $query = 'UPDATE {:' . $this->table . ':} SET ' . implode(', ', $update_fields);
        if (count($this->condition)){
            $query .= "\nWHERE " . $this->condition->getString($this->queryIdentifier);
            $update_values = array_merge($update_values, $this->condition->getArguments($this->queryIdentifier));
        }
        return array('sql'=>$query,'arguments'=>$update_values);
    }

}
