# KanAI local-LLM model guide

How the choice of local model affects KanAI. The first three rows are **measured**
on this setup; the rest are **predictions** based on those measurements plus each
model's known characteristics. Your hardware changes the absolute seconds — treat
latency as *relative*.

## What KanAI asks of a model

Each question sends a system prompt + a retrieval block of project data and asks
for one strict JSON envelope `{"answer": "...markdown...", "proposals": [ ... ]}`.
So a good KanAI model needs three things: **instruction-following** (respect the
JSON contract), **reasoning** (pick the *right* cleanup/next actions, not just
plausible ones), and **speed** (it runs synchronously per question). KanAI now
forces `response_format=json_object`, so envelope reliability is high even for
small models — the differentiator becomes answer *quality*, not parse success.

## Measured (Ollama, this machine, JSON mode on)

| Model | Size | Latency median | Reliability | Proposal quality | Answer depth |
|---|---|---|---|---|---|
| qwen2.5-coder:7b | 4.7 GB | **~3s** (max 48s) | 100% | thin (short, terse) | short (~150 chars) |
| gemma3:12b-it-qat | 8.9 GB | ~23s | 100% | **rich** (14 proposals / 6 runs, good reasons) | fuller (~380 chars) |
| qwen3:14b | 9.3 GB | ~120s | 100% | strong + explained | deepest (~520 chars) |

Notes from the runs:
- **JSON reliability is now 100% across all three** — before JSON mode, the 7B
  model needed the repair retry or failed. This is the single biggest robustness
  win for small models.
- **qwen2.5-coder:7b** is a *coder* model: extremely fast and JSON-perfect, but it
  under-explains and proposes conservatively. Great for snappy Q&A and simple
  tidy-ups; weaker at judgement-heavy asks (risks, workload balancing).
- **gemma3:12b** is the sweet spot here: rich, well-reasoned proposals at a
  tolerable ~20s.
- **qwen3:14b** reasons visibly better but its "thinking" makes it ~2 min/answer —
  fine for the background digest, frustrating for live chat.

## Predictions up to 80B

Extrapolated from the measured curve + model reputations. Latency assumes similar
consumer hardware (Apple Silicon / a single 24–48 GB GPU); on a small box the big
models won't fit in memory at all (the RAM/VRAM column is the gate).

| Model (class) | Params | ~RAM/VRAM (Q4) | Expected latency* | KanAI experience |
|---|---|---|---|---|
| llama3.2:3b / qwen3:4b | 3–4B | ~3–4 GB | 1–3s | Fastest. Fine for summaries/Q&A; weakest judgement, occasional shallow proposals. Good default on a laptop. |
| qwen2.5-coder:7b *(measured)* | 7B | ~5 GB | ~3s | Snappy, JSON-perfect, terse. Good all-round entry model. |
| llama3.1:8b / qwen3:8b | 8B | ~6 GB | 3–6s | Better prose than the coder-7B at similar speed; solid general pick. |
| gemma3:12b *(measured)* | 12B | ~9 GB | ~20s | **Best balance** — rich, well-reasoned proposals; usable live. |
| qwen3:14b *(measured)* | 14B | ~9 GB | ~120s† | Strong reasoning, but reasoning-model latency hurts live chat; ideal for the cron digest. |
| mixtral:8x7b (MoE) | 47B (13B active) | ~28 GB | 8–20s | MoE = only ~13B active, so faster than dense 30B+. Strong general reasoning; great digest + decent live if it fits. |
| qwen2.5:32b / gemma2:27b | 27–32B | ~20 GB | 30–90s | Noticeably better judgement on cleanup/risk/workload; too slow for snappy chat, excellent for digest. |
| llama3.3:70b / qwen2.5:72b | 70–72B | ~42 GB | 60–180s | Best local answer quality — nuanced proposals, reliable prioritisation. Needs a big GPU/lots of RAM; digest-grade latency. |
| qwen2.5:72b-instruct + speculative / 80B-class | ~72–80B | 45–48 GB | 60–200s | Diminishing returns over 70B for KanAI's task; the JSON/action contract caps how much extra size helps. Choose only if you already run it. |

\* Time to a full answer (~500–900 tokens), model already loaded. First call after
idle adds model-load time.
† Reasoning models emit hidden "thinking" tokens, so latency is higher than a dense
model of the same size.

## Recommendations

- **Live chat that feels instant:** a 7–12B model. `qwen2.5-coder:7b` for pure
  speed, `gemma3:12b` for the best quality/speed balance (recommended default).
- **Autonomous daily digest (cron, latency doesn't matter):** go as large as your
  hardware allows — 27B–70B give clearly better cleanup/risk analysis. You can run
  a fast model for live chat and point the digest at a bigger one later (a
  per-context model override is a natural future enhancement).
- **Small laptop / no GPU:** a 3–8B model; KanAI stays fully usable thanks to
  forced JSON mode.
- **Diminishing returns:** past ~70B the JSON+action contract limits how much extra
  capability KanAI can express. Bigger mainly buys sharper prioritisation and
  wording, not new abilities.

## The impact of size on KanAI, in one line per axis

- **Reliability (valid JSON/actions):** flat and high at all sizes now (JSON mode) —
  no longer a reason to go big.
- **Judgement (are the *right* tasks flagged/prioritised?):** rises steadily with
  size; the clearest reason to use a larger model, especially for cleanup, risks
  and workload.
- **Latency:** rises with size (and sharply for reasoning models) — the main reason
  to stay small for live chat.
- **Hardware:** the hard gate — 3–14B run anywhere, 27–70B need a serious GPU or
  lots of unified memory.
