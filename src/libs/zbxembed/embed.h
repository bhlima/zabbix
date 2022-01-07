/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#ifndef ZABBIX_EMBED_H
#define ZABBIX_EMBED_H

#include "common.h"
#include "duktape.h"

#define ZBX_ES_LOG_MEMORY_LIMIT	(ZBX_MEBIBYTE * 8)

/* check (and fail) if timeout was exceeded in C function */
#define ZBX_ES_CHECK_TIMEOUT(env) \
	do { \
		zbx_uint64_t	elapsed_ms; \
		elapsed_ms = zbx_get_duration_ms(&env->start_time); \
		if (elapsed_ms >= env->timeout * 1000) \
			return duk_throw(ctx); \
	} \
	while (0);

struct zbx_es_env
{
	duk_context	*ctx;
	size_t		total_alloc;
	zbx_timespec_t	start_time;

	char		*error;
	int		rt_error_num;
	int		fatal_error;
	int		timeout;
	struct zbx_json	*json;

	jmp_buf		loc;
};

#endif /* ZABBIX_EMBED_H */
