import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js/+esm'

const supabaseUrl = 'https://wcjmjyqadcevocathzjc.supabase.co'
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Indjam1qeXFhZGNldm9jYXRoempjIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODYxMDU0NTcsImV4cCI6MjEwMTY4MTQ1N30.LgWXoNQPL_ofIo1DgPSQC2dbvFeTgLxufxKwcdku5Y4' // Sua chave do print

export const supabase = createClient(supabaseUrl, supabaseKey)