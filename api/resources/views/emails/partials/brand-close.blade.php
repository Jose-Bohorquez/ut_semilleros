{{-- Pie común: WhatsApp (el mismo número del botón «Reportar bug» del sistema). Variables: $reason, $whatsappUrl, $waTitle (opcional) --}}
    <tr><td style="padding:0 32px 24px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
        <tr><td style="padding:16px 18px;font-size:14px;line-height:1.6;color:#14532d;">
          <strong>{{ $waTitle ?? '¿Necesitas información, quieres cancelar tu registro o encontraste una falla?' }}</strong><br>
          Escríbenos por WhatsApp. Es el mismo canal del botón verde «Reportar bug» del sistema.
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:12px;"><tr>
            <td style="border-radius:999px;background:#15803d;">
              <a href="{{ $whatsappUrl }}" style="display:inline-block;padding:10px 22px;font-size:14px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:999px;">💬 Escribir por WhatsApp</a>
            </td>
          </tr></table>
        </td></tr>
      </table>
    </td></tr>

    <tr><td style="background:#f8fafc;padding:18px 32px;font-size:12px;line-height:1.6;color:#64748b;">
      {{ $reason }}<br>
      Tratamiento de datos: <a href="https://administrativos.ut.edu.co/images/RES._0676_DEL_27-05-19_ADOPTA_MANUAL_DE_POLITICAS.pdf" style="color:#64748b;">Resolución 0676 de 2019</a>.
    </td></tr>

  </table>
</td></tr>
</table>
</body>
</html>
