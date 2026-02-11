# Skill: Designer Tailwind

Esta skill garante consistência visual lendo as configurações do projeto antes de gerar CSS.

## Regras de Estilização
1. **Consulte a Configuração:** Antes de sugerir classes de cores ou fontes, leia o arquivo `tailwind.config.js` (ou `tailwind.config.ts`).
2. **Use Variáveis do Tema:**
   - Nunca use cores arbitrárias (ex: `bg-[#123456]`) se houver uma cor no tema.
   - Prefira `bg-primary-500`, `text-secondary`, etc., se estiverem definidas na config.
3. **Padrão Mobile-First:** Use prefixos (sm:, md:, lg:) para responsividade.

## Cores Proibidas
Evite cores padrão do Tailwind (ex: `blue-500`, `red-600`) a menos que o usuário não tenha definido uma paleta personalizada.
