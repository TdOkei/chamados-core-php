#!/usr/bin/env python3
"""
Gerador de Excel para exportação do histórico de solicitações.
Uso: python3 gerar_excel.py <json_input_file> <output_file>
"""
import sys
import json
from openpyxl import Workbook
from openpyxl.styles import (
    Font, PatternFill, Alignment, Border, Side, GradientFill
)
from openpyxl.utils import get_column_letter
from datetime import datetime

def thin_border():
    s = Side(style='thin', color='CCCCCC')
    return Border(left=s, right=s, top=s, bottom=s)

def main():
    if len(sys.argv) < 3:
        print(json.dumps({'success': False, 'error': 'Argumentos insuficientes'}))
        sys.exit(1)

    input_file  = sys.argv[1]
    output_file = sys.argv[2]

    with open(input_file, 'r', encoding='utf-8') as f:
        data = json.load(f)

    registros = data.get('registros', [])
    filtros   = data.get('filtros', {})
    totais    = data.get('totais', {})

    wb = Workbook()

    # ----------------------------------------------------------------
    # ABA 1: Solicitações
    # ----------------------------------------------------------------
    ws = wb.active
    ws.title = 'Histórico de Solicitações'

    # Paleta
    COR_HEADER_BG  = '09090B'  # --primary
    COR_HEADER_FG  = 'FFFFFF'
    COR_SUBHEADER  = 'F4F4F5'  # --neutral-100
    COR_BRAND      = 'FD3A25'  # --brand
    COR_DONE       = '16a34a'
    COR_CANCELLED  = '71717A'
    COR_REJECTED   = 'DC2626'

    STATUS_LABELS = {
        'concluido':  'Concluído',
        'cancelado':  'Cancelado',
        'rejeitado':  'Rejeitado',
    }

    STATUS_CORES = {
        'concluido': ('DCFCE7', '14532D'),
        'cancelado': ('F4F4F5', '71717A'),
        'rejeitado': ('FEE2E2', '991B1B'),
    }

    # --- Título principal ---
    ws.merge_cells('A1:J1')
    titulo = ws['A1']
    titulo.value = 'HISTÓRICO DE SOLICITAÇÕES — SECRETARIA DE ENSINO'
    titulo.font  = Font(name='Arial', bold=True, size=14, color=COR_HEADER_FG)
    titulo.fill  = PatternFill('solid', start_color=COR_HEADER_BG)
    titulo.alignment = Alignment(horizontal='center', vertical='center')
    ws.row_dimensions[1].height = 32

    # --- Subtítulo com data de geração e filtros ---
    ws.merge_cells('A2:J2')
    sub = ws['A2']
    filtro_str = []
    if filtros.get('data_inicio'): filtro_str.append(f"De: {filtros['data_inicio']}")
    if filtros.get('data_fim'):    filtro_str.append(f"Até: {filtros['data_fim']}")
    if filtros.get('escola'):      filtro_str.append(f"Escola: {filtros['escola']}")
    if filtros.get('status'):      filtro_str.append(f"Status: {STATUS_LABELS.get(filtros['status'], filtros['status'])}")
    if filtros.get('categoria'):   filtro_str.append(f"Categoria: {filtros['categoria'].capitalize()}")
    filtro_text = '   |   '.join(filtro_str) if filtro_str else 'Todos os registros'
    sub.value = f"Gerado em {datetime.now().strftime('%d/%m/%Y às %H:%M')}   —   Filtros: {filtro_text}"
    sub.font  = Font(name='Arial', size=9, color='71717A')
    sub.alignment = Alignment(horizontal='center', vertical='center')
    ws.row_dimensions[2].height = 18

    # --- Cards de totais ---
    ws.merge_cells('A3:C3')
    ws.merge_cells('D3:F3')
    ws.merge_cells('G3:H3')
    ws.merge_cells('I3:J3')

    cards = [
        ('A3', f"✓ {totais.get('concluidas', 0)} Concluídas",  'DCFCE7', '14532D'),
        ('D3', f"✗ {totais.get('canceladas', 0)} Canceladas",  'F4F4F5', '71717A'),
        ('G3', f"⊘ {totais.get('rejeitadas', 0)} Rejeitadas",  'FEE2E2', '991B1B'),
        ('I3', f"Σ {totais.get('total', 0)} Total",             'EFF6FF', '1E40AF'),
    ]
    for cell_id, texto, bg, fg in cards:
        c = ws[cell_id]
        c.value = texto
        c.font  = Font(name='Arial', bold=True, size=11, color=fg)
        c.fill  = PatternFill('solid', start_color=bg)
        c.alignment = Alignment(horizontal='center', vertical='center')
    ws.row_dimensions[3].height = 28

    # --- Linha vazia ---
    ws.row_dimensions[4].height = 6

    # --- Cabeçalhos da tabela ---
    cabecalhos = [
        ('A', '#',             6),
        ('B', 'ID',            8),
        ('C', 'Escola',        28),
        ('D', 'Diretor(a)',    22),
        ('E', 'Categoria',     12),
        ('F', 'Status',        14),
        ('G', 'Descrição/Itens', 35),
        ('H', 'Responsável',   20),
        ('I', 'Criada em',     16),
        ('J', 'Encerrada em',  16),
    ]
    header_row = 5
    for col_letter, label, width in cabecalhos:
        c = ws[f'{col_letter}{header_row}']
        c.value = label
        c.font  = Font(name='Arial', bold=True, size=10, color=COR_HEADER_FG)
        c.fill  = PatternFill('solid', start_color=COR_HEADER_BG)
        c.alignment = Alignment(horizontal='center', vertical='center', wrap_text=True)
        c.border = thin_border()
        ws.column_dimensions[col_letter].width = width
    ws.row_dimensions[header_row].height = 22

    # --- Dados ---
    for idx, r in enumerate(registros, start=1):
        row = header_row + idx
        bg_base = 'FFFFFF' if idx % 2 == 0 else 'FAFAFA'

        status_key = r.get('status', '')
        status_cores = STATUS_CORES.get(status_key, ('F4F4F5', '374151'))
        status_label = STATUS_LABELS.get(status_key, status_key.capitalize())

        # Monta descrição: itens de produto ou texto do serviço
        itens = r.get('itens', [])
        if itens:
            desc = '; '.join(f"{i['produto']} ({float(i['quantidade']):.2g} {i['unidade']})" for i in itens)
        elif r.get('descricao_servico'):
            desc = r['descricao_servico']
        else:
            desc = '—'

        # Observações adicionais
        extras = []
        if r.get('motivo_rejeicao'):  extras.append(f"Motivo rejeição: {r['motivo_rejeicao']}")
        if r.get('obs_item_falta'):   extras.append(f"Item em falta: {r['obs_item_falta']}")
        if r.get('nome_recebedor'):   extras.append(f"Recebido por: {r['nome_recebedor']}")
        if extras: desc += '\n' + '\n'.join(extras)

        valores = [
            idx,
            f"#{str(r.get('id','')).zfill(4)}",
            r.get('escola_nome', ''),
            r.get('diretor_nome', ''),
            r.get('categoria', '').capitalize(),
            status_label,
            desc,
            r.get('admin_nome', '—'),
            r.get('created_at', ''),
            r.get('updated_at', ''),
        ]

        for ci, val in enumerate(valores, start=1):
            c = ws.cell(row=row, column=ci, value=val)
            c.font      = Font(name='Arial', size=9)
            c.alignment = Alignment(vertical='center', wrap_text=True)
            c.border    = thin_border()

            # Colorir coluna de status
            if ci == 6:
                c.fill = PatternFill('solid', start_color=status_cores[0])
                c.font = Font(name='Arial', size=9, bold=True, color=status_cores[1])
                c.alignment = Alignment(horizontal='center', vertical='center')
            elif ci == 5:
                cat_bg = 'DBEAFE' if val == 'Produto' else 'FEF9C3'
                cat_fg = '1E40AF' if val == 'Produto' else '854D0E'
                c.fill = PatternFill('solid', start_color=cat_bg)
                c.font = Font(name='Arial', size=9, color=cat_fg, bold=True)
                c.alignment = Alignment(horizontal='center', vertical='center')
            elif ci == 1:
                c.fill = PatternFill('solid', start_color=COR_SUBHEADER)
                c.font = Font(name='Arial', size=9, color='71717A')
                c.alignment = Alignment(horizontal='center', vertical='center')
            else:
                c.fill = PatternFill('solid', start_color=bg_base)

        # Altura da linha proporcional ao conteúdo
        linhas_desc = max(1, desc.count('\n') + 1)
        ws.row_dimensions[row].height = max(16, linhas_desc * 14)

    # Freeze panes
    ws.freeze_panes = f'A{header_row + 1}'

    # Auto-filter
    ws.auto_filter.ref = f'A{header_row}:J{header_row}'

    # ----------------------------------------------------------------
    # ABA 2: Log de QR Code
    # ----------------------------------------------------------------
    ws2 = wb.create_sheet('Log QR Code')

    ws2.merge_cells('A1:G1')
    t2 = ws2['A1']
    t2.value = 'LOG DE LEITURAS DE QR CODE'
    t2.font  = Font(name='Arial', bold=True, size=13, color=COR_HEADER_FG)
    t2.fill  = PatternFill('solid', start_color=COR_HEADER_BG)
    t2.alignment = Alignment(horizontal='center', vertical='center')
    ws2.row_dimensions[1].height = 28

    log_headers = [
        ('A', 'ID',            8),
        ('B', 'Solicitação',   12),
        ('C', 'Escola',        26),
        ('D', 'Diretor(a)',    22),
        ('E', 'Resultado',     16),
        ('F', 'Observação',    35),
        ('G', 'Data/Hora',     18),
    ]
    for col_l, label, width in log_headers:
        c = ws2[f'{col_l}2']
        c.value = label
        c.font  = Font(name='Arial', bold=True, size=10, color=COR_HEADER_FG)
        c.fill  = PatternFill('solid', start_color=COR_HEADER_BG)
        c.alignment = Alignment(horizontal='center', vertical='center')
        c.border = thin_border()
        ws2.column_dimensions[col_l].width = width
    ws2.row_dimensions[2].height = 20

    RESULTADO_CORES = {
        'sucesso':      ('DCFCE7', '14532D'),
        'retornado':    ('FEE2E2', '991B1B'),
        'item_em_falta':('FFEDD5', '9A3412'),
        'erro_token':   ('FEE2E2', '991B1B'),
        'erro_destino': ('FEF9C3', '854D0E'),
    }
    RESULTADO_LABELS = {
        'sucesso':       'Sucesso',
        'retornado':     'Retornado',
        'item_em_falta': 'Item em falta',
        'erro_token':    'Erro de token',
        'erro_destino':  'Destino errado',
    }

    logs = data.get('log_qr', [])
    for idx, lg in enumerate(logs, start=1):
        row = idx + 2
        res = lg.get('resultado', '')
        res_cores = RESULTADO_CORES.get(res, ('F4F4F5', '374151'))
        res_label = RESULTADO_LABELS.get(res, res)
        bg_base = 'FFFFFF' if idx % 2 == 0 else 'FAFAFA'

        vals = [
            idx,
            f"#{str(lg.get('id_solicitacao','')).zfill(4)}",
            lg.get('escola_nome', '—'),
            lg.get('diretor_nome', ''),
            res_label,
            lg.get('observacao', ''),
            lg.get('created_at', ''),
        ]
        for ci, val in enumerate(vals, start=1):
            c = ws2.cell(row=row, column=ci, value=val)
            c.font   = Font(name='Arial', size=9)
            c.border = thin_border()
            c.alignment = Alignment(vertical='center', wrap_text=True)
            if ci == 5:
                c.fill = PatternFill('solid', start_color=res_cores[0])
                c.font = Font(name='Arial', size=9, bold=True, color=res_cores[1])
                c.alignment = Alignment(horizontal='center', vertical='center')
            else:
                c.fill = PatternFill('solid', start_color=bg_base)
        ws2.row_dimensions[row].height = 16

    ws2.freeze_panes = 'A3'
    ws2.auto_filter.ref = 'A2:G2'

    # ----------------------------------------------------------------
    # Salva
    # ----------------------------------------------------------------
    wb.save(output_file)
    print(json.dumps({'success': True, 'registros': len(registros), 'logs': len(logs)}))

if __name__ == '__main__':
    main()
