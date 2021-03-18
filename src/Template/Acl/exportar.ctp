<?php 

/**
 * CakePHP 3.x - Acl Manager
 * 
 * PHP version 5
 * 
 * permissions.ctp
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @category CakePHP3
 * 
 * @author Ivan Amat <dev@ivanamat.es>
 * @copyright Copyright 2016, Iván Amat
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/ivanamat/cakephp3-aclmanager
 */

use Cake\Core\Configure; 
use Cake\Utility\Inflector;

?>


<?php if($this->request->session()->read('Flash')) { ?>
<div class="row panel">
    <div class="columns large-12">
        <h3>Response</h3>
        <hr />
        <?php echo $this->Flash->render(); ?>
    </div>
</div>
<?php } ?>

<div class="row">
    <div class="col-md-12">
        <h2><?php echo sprintf(__($model)); ?></h2>
        <hr />

        <table class="table table-bordered table-hover table-striped dataTable">
            <thead>
                <tr>
                    <th>Action</th>
                    <?php foreach ($aros as $aro): ?>
                        <?php $aro = array_shift($aro); ?>
                        <th>
                            <?php
                            $parentNode = $aro->parentNode();
                            if (!is_null($parentNode)) {
                                $key = key($parentNode);
                                $subKey = key($parentNode[$key]);
                                $gData = $this->AclManager->getName($key, $parentNode[key($parentNode)][$subKey]);
                                echo h($aro[$aroDisplayField]) . ' ( ' . $gData['name'] . ' )';
                            } else {
                                echo h($aro[$aroDisplayField]);
                            }
                            ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $uglyIdent = Configure::read('AclManager.uglyIdent');
                $lastIdent = null;
                foreach ($acos as $id => $aco) {
                    $action = $aco['Action'];
                    $alias = $aco['Aco']['alias'];
                    $ident = substr_count($action, '/');

                    if ($ident <= $lastIdent && !is_null($lastIdent)) {
                        for ($i = 0; $i <= ($lastIdent - $ident); $i++) {
                            echo "</tr>";
                        }
                    }

                    if ($ident != $lastIdent) {
                        echo "<tr class='aclmanager-ident-" . $ident . "'>";
                    }
                    
                    $uAllowed = true;
                    if($hideDenied) {
                        $uAllowed = $this->AclManager->Acl->check(['Users' => ['id' => $this->request->session()->read('Auth.User.id')]], $action);
                    }

                    if ($uAllowed) {
                        echo "<td>";
                        echo Inflector::humanize(($ident == 1 ? "<strong>" : "" ) . ($uglyIdent ? str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $ident) : "") . h($alias) . ($ident == 1 ? "</strong>" : "" ));
                        echo "</td>";

                        foreach ($aros as $aro):
                            $inherit = $this->AclManager->value("Perms." . str_replace("/", ":", $action) . ".{$aroAlias}:{$aro[$aroAlias]['id']}-inherit");
                            $allowed = $this->AclManager->value("Perms." . str_replace("/", ":", $action) . ".{$aroAlias}:{$aro[$aroAlias]['id']}");

                            $mAro = $model;
                            $mAllowed = $this->AclManager->Acl->check($aro, $action);
                            $mAllowedText = ($mAllowed) ? 'Allow' : 'Deny';

                            // Originally based on 'allowed' above 'mAllowed'
                            $icon = ($mAllowed) ? $this->Html->image('AclManager.allow_32.png') : $this->Html->image('AclManager.deny_32.png');

                            if ($inherit) {
                                $icon = $this->Html->image('AclManager.inherit_32.png');
                            }

                            if ($mAllowed && !$inherit) {
                                $icon = $this->Html->image('AclManager.allow_32.png');
                                $mAllowedText = 'Autorizado';
                            }

                            if ($mAllowed && $inherit) {
                                $icon = $this->Html->image('AclManager.allow_inherited_32.png');
                                $mAllowedText = 'Heredado - SI';
                            }

                            if (!$mAllowed && $inherit) {
                                $icon = $this->Html->image('AclManager.deny_inherited_32.png');
                                $mAllowedText = 'Heredado - NO';
                            }

                            if (!$mAllowed && !$inherit) {
                                $icon = $this->Html->image('AclManager.deny_32.png');
                                $mAllowedText = 'Denegado';
                            }

                            echo "<td class=\"select-perm\">";
                                echo $mAllowedText;
                                    //echo $icon . ' ' . $this->Form->select('', array('inherit' => __('Inherit'), 'allow' => __('Allow'), 'deny' => __('Deny')), array('empty' => __($mAllowedText), 'class' => 'form-control'));
                            echo "</td>";
                        endforeach;

                        $lastIdent = $ident;
                    }
                }

                for ($i = 0; $i <= $lastIdent; $i++) {
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <div class="row">
        <div class="columns large-12">
            <div class="paginator">
                <ul class="pagination">
                    <?php echo $this->Paginator->prev('< ' . __('previous')) ?>
                    <?php echo $this->Paginator->numbers() ?>
                    <?php echo $this->Paginator->next(__('next') . ' >') ?>
                </ul>
                <p><?php echo $this->Paginator->counter() ?></p>
            </div>
             <button type="submit" class="btn btn-primary right"><?php echo __("Save"); ?></button>
        </div>
    </div>    
</div>

<script>
    $(document).ready(function () {
        $('.dataTable').DataTable({
            pageLength: 20,
            responsive: true,
            ordering: false,
            dom: "<'row'<'col-sm-6'f><'col-sm-6 botones-tabla-top-right'B>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row'<'col-sm-6'i><'col-sm-6'p>>",
            buttons: [
                {text:"<i class='fa fa-file-excel-o'>Excel</i>", titleAtrr: "Excel", extend: 'excel',
                    exportOptions: {
                        format: {
                            body: function ( data, row, column, node ) {
                                // Strip $ from salary column to make it numeric
                                return column === 5 ?
                                    data.replace( /[$,]/g, '' ) :
                                    data;
                            }
                        }
                    }                    
                },
                {extend: 'pdf', title: 'ExampleFile', titleAtrr: "PDF", text:"<i class='fa fa-file-pdf-o'>PDF</i>"},
                {extend: 'print',
                    customize: function (win) {
                        $(win.document.body).addClass('white-bg');
                        $(win.document.body).css('font-size', '8px');
                        $(win.document.body).find('table')
                                .addClass('compact')
                                .css('font-size', 'inherit');
                    }
                }
            ]
        });
    });
</script>